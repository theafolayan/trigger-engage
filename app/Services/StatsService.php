<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\DeliveryStatus;
use App\Models\Automation;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\Event;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Horizon;

class StatsService
{
    /**
     * Get aggregate totals for the given workspace or globally.
     *
     * @return array<string, mixed>
     */
    public function totals(?Workspace $workspace = null): array
    {
        $contactQuery = Contact::query()->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id));
        $deliveryQuery = Delivery::query()->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id));
        $eventQuery = Event::query()->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id));

        $totalContacts = $contactQuery->count();
        $activeContacts = (clone $contactQuery)->where('status', ContactStatus::Active)->count();
        $suppressedContacts = $totalContacts - $activeContacts;

        $sentDeliveries = (clone $deliveryQuery)->whereIn('status', [
            DeliveryStatus::Sent,
            DeliveryStatus::Bounced,
            DeliveryStatus::Complained,
            DeliveryStatus::Failed,
        ])->count();

        $delivered = (clone $deliveryQuery)->where('status', DeliveryStatus::Sent)->count();
        $bounced = (clone $deliveryQuery)->where('status', DeliveryStatus::Bounced)->count();
        $complained = (clone $deliveryQuery)->where('status', DeliveryStatus::Complained)->count();
        $failed = (clone $deliveryQuery)->where('status', DeliveryStatus::Failed)->count();

        $opens = (clone $eventQuery)->where('name', 'open')->count();
        $clicks = (clone $eventQuery)->where('name', 'click')->count();
        $unsubscribes = (clone $contactQuery)->where('status', ContactStatus::Unsubscribed)->count();

        $openRate = $sentDeliveries > 0 ? $opens / $sentDeliveries : 0.0;
        $clickRate = $sentDeliveries > 0 ? $clicks / $sentDeliveries : 0.0;
        $unsubscribeRate = $totalContacts > 0 ? $unsubscribes / $totalContacts : 0.0;

        return [
            'contacts' => [
                'total' => $totalContacts,
                'active' => $activeContacts,
                'suppressed' => $suppressedContacts,
            ],
            'deliveries' => [
                'sent' => $sentDeliveries,
                'delivered' => $delivered,
                'bounced' => $bounced,
                'complained' => $complained,
                'failed' => $failed,
            ],
            'rates' => [
                'open' => $openRate,
                'click' => $clickRate,
                'unsubscribe' => $unsubscribeRate,
            ],
            'events_ingested' => $eventQuery->count(),
            'automations_executed' => $deliveryQuery->count(),
        ];
    }

    /**
     * Deliveries per day for last 30 days.
     *
     * @return array<string, int>
     */
    public function deliveriesPerDay(?Workspace $workspace = null): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $query = Delivery::query()
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', $start)
            ->selectRaw('date(sent_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day');

        return $query->pluck('total', 'day')->all();
    }

    /**
     * Bounce and complaint trend grouped by day.
     *
     * @return array{bounced: array<string,int>, complained: array<string,int>}
     */
    public function bouncesComplaintsTrend(?Workspace $workspace = null): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $base = Delivery::query()
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->whereIn('status', [DeliveryStatus::Bounced, DeliveryStatus::Complained])
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', $start)
            ->selectRaw('date(sent_at) as day, status, count(*) as total')
            ->groupBy('day', 'status')
            ->get();

        $bounced = [];
        $complained = [];

        foreach ($base as $row) {
            if ($row->status === DeliveryStatus::Bounced) {
                $bounced[$row->day] = (int) $row->total;
            }
            if ($row->status === DeliveryStatus::Complained) {
                $complained[$row->day] = (int) $row->total;
            }
        }

        return [
            'bounced' => $bounced,
            'complained' => $complained,
        ];
    }

    /**
     * Top automations by send count.
     *
     * @return array<string,int>
     */
    public function topAutomations(?Workspace $workspace = null): array
    {
        $query = Delivery::query()
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->whereNotNull('automation_id')
            ->selectRaw('automation_id, count(*) as total')
            ->groupBy('automation_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('automation:id,name');

        $data = [];
        foreach ($query->get() as $row) {
            /** @var Delivery $row */
            $name = $row->automation?->name ?? 'Unknown';
            $data[$name] = (int) $row->total;
        }

        return $data;
    }

    /**
     * Basic system health indicators.
     *
     * @return array<string,mixed>
     */
    public function systemHealth(): array
    {
        return [
            'queue_size' => Queue::size(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'horizon_status' => Horizon::status(),
        ];
    }
}
