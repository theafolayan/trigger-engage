<?php

declare(strict_types=1);

namespace App\Jobs\Twitter;

use App\Models\TwitterAccount;
use App\Services\Twitter\DirectMessageSender;
use App\Services\Twitter\Exceptions\RateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDirectMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $connection;

    public string $queue;

    public function __construct(
        public TwitterAccount $twitterAccount,
        public string $participantId,
        public string $message,
    ) {
        $this->connection = config('twitter.queues.connection', 'redis');
        $this->queue = config('twitter.queues.dm_queue', 'twitter-dm');
        $this->onConnection($this->connection);
        $this->onQueue($this->queue);
    }

    public function handle(DirectMessageSender $sender): void
    {
        $account = $this->twitterAccount->fresh();
        if ($account === null || $account->disconnected_at !== null) {
            return;
        }

        try {
            $sender->send($account, $this->participantId, $this->message);
        } catch (RateLimitException $exception) {
            $delay = max(1, $exception->retryAfter ?? config('twitter.dm.backoff.default_retry_after'));
            $this->release($delay);
        } catch (\Throwable $exception) {
            Log::error('Failed to send Twitter direct message', [
                'twitter_account_id' => $account->id,
                'participant_id' => $this->participantId,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
