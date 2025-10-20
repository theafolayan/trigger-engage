<?php

declare(strict_types=1);

namespace App\Jobs\Twitter;

use App\Models\TwitterAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchFollowerPolling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $connection;

    public string $queue;

    public function __construct(public string $planName)
    {
        $this->connection = config('twitter.queues.connection', 'redis');
        $this->queue = config('twitter.queues.poll_queue', 'twitter-polling');
        $this->onConnection($this->connection);
        $this->onQueue($this->queue);
    }

    public function handle(): void
    {
        $accounts = TwitterAccount::query()
            ->whereNull('disconnected_at')
            ->whereHas('workspace.account.plan', function ($query): void {
                $query->where('name', $this->planName);
            })
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        foreach ($accounts as $account) {
            if (empty($account->access_token)) {
                Log::warning('Skipping Twitter follower polling for account without access token', [
                    'twitter_account_id' => $account->id,
                ]);
                continue;
            }

            PollFollowers::dispatch($account)->onConnection($this->connection)->onQueue($this->queue);
        }
    }
}
