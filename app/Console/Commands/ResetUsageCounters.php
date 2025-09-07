<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\UsageTracker;
use Illuminate\Console\Command;

class ResetUsageCounters extends Command
{
    protected $signature = 'usage:reset';
    protected $description = 'Reset monthly usage counters';

    public function handle(UsageTracker $tracker): int
    {
        foreach (Workspace::all() as $workspace) {
            $counter = $tracker->counter($workspace);
            $counter->update([
                'emails_sent' => 0,
                'events_ingested' => 0,
                'contacts_created' => 0,
            ]);
        }

        $this->info('Usage counters reset');
        return self::SUCCESS;
    }
}
