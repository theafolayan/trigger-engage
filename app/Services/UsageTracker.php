<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UsageCounter;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

class UsageTracker
{
    public function counter(Workspace $workspace, ?Carbon $date = null): UsageCounter
    {
        $date = $date ?? now();
        return UsageCounter::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'month' => $date->month,
                'year' => $date->year,
            ],
            [
                'emails_sent' => 0,
                'events_ingested' => 0,
                'contacts_created' => 0,
            ]
        );
    }

    public function recordEmailsSent(Workspace $workspace, int $count = 1): UsageCounter
    {
        $counter = $this->counter($workspace);
        $counter->increment('emails_sent', $count);
        return $counter->fresh();
    }

    public function recordEventsIngested(Workspace $workspace, int $count = 1): UsageCounter
    {
        $counter = $this->counter($workspace);
        $counter->increment('events_ingested', $count);
        return $counter->fresh();
    }

    public function recordContactsCreated(Workspace $workspace, int $count = 1): UsageCounter
    {
        $counter = $this->counter($workspace);
        $counter->increment('contacts_created', $count);
        return $counter->fresh();
    }
}
