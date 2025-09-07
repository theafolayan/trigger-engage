<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Workspace;
use RuntimeException;

class PlanEnforcer
{
    public function __construct(private UsageTracker $tracker)
    {
    }

    public function recordEmailsSent(Workspace $workspace, int $count = 1): void
    {
        $plan = $workspace->account->plan;
        $counter = $this->tracker->counter($workspace);
        if ($plan->email_quota !== null && ($counter->emails_sent + $count) > $plan->email_quota) {
            throw new RuntimeException('Email quota exceeded');
        }

        $this->tracker->recordEmailsSent($workspace, $count);
    }

    public function ensureFeature(Workspace $workspace, string $feature): void
    {
        if (! $workspace->account->plan->hasFeature($feature)) {
            throw new RuntimeException('Feature not available');
        }
    }
}
