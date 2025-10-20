<?php

declare(strict_types=1);

namespace App\Services\Twitter;

use App\Models\TwitterAccount;
use App\Services\RateLimiter\TokenBucketLimiter;
use App\Services\Twitter\Exceptions\DirectMessageException;
use App\Services\Twitter\Exceptions\RateLimitException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DirectMessageSender
{
    public function __construct(private TokenBucketLimiter $limiter)
    {
    }

    public function send(TwitterAccount $account, string $participantId, string $message): void
    {
        $this->enforceRateLimits($account);

        $token = $account->access_token;
        if ($token === null) {
            throw new DirectMessageException('Twitter access token is missing for account '.$account->id);
        }

        $attempts = max(1, (int) config('twitter.dm.backoff.attempts', 3));
        $delay = max(1, (int) config('twitter.dm.backoff.initial_seconds', 2));
        $endpoint = sprintf('%s/2/dm_conversations/with/%s/messages', config('twitter.base_url'), $participantId);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $attempts) {
            $attempt++;

            try {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post($endpoint, [
                        'text' => $message,
                    ]);

                if ($response->successful()) {
                    Log::info('Twitter direct message sent', [
                        'twitter_account_id' => $account->id,
                        'participant_id' => $participantId,
                    ]);
                    return;
                }

                if ($response->status() === 429) {
                    $retryAfter = $this->parseRetryAfter($response->header('retry-after'));
                    throw new RateLimitException('Twitter API rate limit exceeded.', $retryAfter);
                }

                $response->throw();
            } catch (RateLimitException $exception) {
                Log::warning('Twitter direct message hit API rate limit', [
                    'twitter_account_id' => $account->id,
                    'participant_id' => $participantId,
                    'retry_after' => $exception->retryAfter,
                ]);
                throw $exception;
            } catch (RequestException $exception) {
                $lastException = $exception;
            } catch (Throwable $exception) {
                $lastException = $exception;
            }

            if ($attempt >= $attempts) {
                break;
            }

            sleep($delay);
            $delay *= 2;
        }

        $errorMessage = $lastException instanceof Throwable ? $lastException->getMessage() : 'Unknown error';
        throw new DirectMessageException('Unable to send Twitter direct message: '.$errorMessage, previous: $lastException);
    }

    private function enforceRateLimits(TwitterAccount $account): void
    {
        $limits = config('twitter.dm.rate_limits');

        $perUser = $limits['per_user'] ?? null;
        if ($perUser !== null && ($perUser['per_minute'] ?? 0) > 0) {
            $key = ($perUser['key_prefix'] ?? 'twitter:dm:user:').$account->id;
            if (! $this->limiter->consumeKey($key, (int) $perUser['per_minute'])) {
                $retryAfter = (int) config('twitter.dm.backoff.default_retry_after', 60);
                throw new RateLimitException('Per-user DM rate limit reached.', $retryAfter);
            }
        }

        $perApp = $limits['per_app'] ?? null;
        if ($perApp !== null && ($perApp['per_minute'] ?? 0) > 0) {
            $key = $perApp['key'] ?? 'twitter:dm:app';
            if (! $this->limiter->consumeKey($key, (int) $perApp['per_minute'])) {
                $retryAfter = (int) config('twitter.dm.backoff.default_retry_after', 60);
                throw new RateLimitException('App-wide DM rate limit reached.', $retryAfter);
            }
        }
    }

    private function parseRetryAfter(?string $header): int
    {
        if ($header === null) {
            return (int) config('twitter.dm.backoff.default_retry_after', 60);
        }

        if (is_numeric($header)) {
            return max(1, (int) $header);
        }

        try {
            $retry = Carbon::now()->diffInSeconds(Carbon::parse($header));
            return max(1, $retry);
        } catch (Throwable) {
            return (int) config('twitter.dm.backoff.default_retry_after', 60);
        }
    }
}
