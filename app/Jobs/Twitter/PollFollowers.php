<?php

declare(strict_types=1);

namespace App\Jobs\Twitter;

use App\Models\TwitterAccount;
use App\Models\TwitterFollower;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollFollowers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public TwitterAccount $twitterAccount)
    {
        $this->connection = config('twitter.queues.connection', 'redis');
        $this->queue = config('twitter.queues.poll_queue', 'twitter-polling');
        $this->onConnection($this->connection);
        $this->onQueue($this->queue);
    }

    public function handle(): void
    {
        $account = $this->twitterAccount->fresh();
        if ($account === null || $account->disconnected_at !== null) {
            return;
        }

        $token = $account->access_token;
        if ($token === null) {
            Log::warning('Twitter polling skipped due to missing access token', [
                'twitter_account_id' => $account->id,
            ]);
            return;
        }

        $url = sprintf('%s/2/users/%s/followers', config('twitter.base_url'), $account->twitter_id);
        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url, [
                'max_results' => config('twitter.polling.page_size'),
                'user.fields' => 'id,name,username',
            ]);

        if ($response->failed()) {
            Log::error('Twitter followers API request failed', [
                'twitter_account_id' => $account->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return;
        }

        $followers = $response->json('data', []);
        if (! is_array($followers)) {
            Log::warning('Twitter followers API returned unexpected payload', [
                'twitter_account_id' => $account->id,
            ]);
            return;
        }

        $welcomeMessage = config('twitter.dm.welcome_message');
        foreach ($followers as $follower) {
            $followerId = $follower['id'] ?? null;
            if ($followerId === null) {
                continue;
            }

            $record = TwitterFollower::query()->firstOrCreate(
                [
                    'twitter_account_id' => $account->id,
                    'follower_id' => $followerId,
                ],
                [
                    'username' => $follower['username'] ?? null,
                    'name' => $follower['name'] ?? null,
                    'seen_at' => Carbon::now(),
                ],
            );

            if (! $record->wasRecentlyCreated) {
                continue;
            }

            SendDirectMessage::dispatch(
                $account,
                $followerId,
                $welcomeMessage,
            )->onConnection(config('twitter.queues.connection', 'redis'))
                ->onQueue(config('twitter.queues.dm_queue', 'twitter-dm'));
        }

        $account->forceFill([
            'last_polled_at' => Carbon::now(),
        ])->save();
    }
}
