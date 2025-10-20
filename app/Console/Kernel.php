<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\Twitter\DispatchFollowerPolling;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $connection = config('twitter.queues.connection', 'redis');
        $queue = config('twitter.queues.poll_queue', 'twitter-polling');

        foreach (config('twitter.polling.plans', []) as $plan => $interval) {
            $interval = (int) $interval;
            if ($interval <= 0) {
                continue;
            }

            $schedule->job(new DispatchFollowerPolling($plan))
                ->cron(sprintf('*/%d * * * *', $interval))
                ->onConnection($connection)
                ->onQueue($queue)
                ->withoutOverlapping()
                ->name(sprintf('twitter-followers-polling-%s', strtolower($plan)));
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
