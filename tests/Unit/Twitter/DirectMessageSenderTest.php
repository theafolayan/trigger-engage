<?php

declare(strict_types=1);

use App\Services\RateLimiter\TokenBucketLimiter;
use App\Services\Twitter\DirectMessageSender;
use App\Services\Twitter\Exceptions\DirectMessageException;
use App\Services\Twitter\Exceptions\RateLimitException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'twitter.dm.rate_limits.per_user.key_prefix' => 'twitter:dm:user:',
        'twitter.dm.rate_limits.per_user.per_minute' => 15,
        'twitter.dm.rate_limits.per_app.key' => 'twitter:dm:app',
        'twitter.dm.rate_limits.per_app.per_minute' => 300,
        'twitter.dm.backoff.attempts' => 1,
        'twitter.dm.backoff.initial_seconds' => 1,
        'twitter.dm.backoff.default_retry_after' => 60,
    ]);
});

afterEach(function (): void {
    \Mockery::close();
});

it('sends a direct message when rate limits allow and the API succeeds', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $limiter = \Mockery::mock(TokenBucketLimiter::class);
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:user:'.$account->id, 15)->andReturnTrue();
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:app', 300)->andReturnTrue();

    Http::fake([
        sprintf('%s/2/dm_conversations/with/%s/messages', config('twitter.base_url'), '111') => Http::response([], 200),
    ]);

    $sender = new DirectMessageSender($limiter);
    $sender->send($account->fresh(), '111', 'Hello there');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/2/dm_conversations/with/111/messages')
            && $request['text'] === 'Hello there';
    });
});

it('throws a rate limit exception when the per-user limiter is exhausted', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $limiter = \Mockery::mock(TokenBucketLimiter::class);
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:user:'.$account->id, 15)->andReturnFalse();

    $sender = new DirectMessageSender($limiter);

    Http::fake();

    expect(fn () => $sender->send($account->fresh(), '222', 'Hello'))
        ->toThrow(RateLimitException::class);

    Http::assertNothingSent();
});

it('throws a rate limit exception when the Twitter API responds with 429', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $limiter = \Mockery::mock(TokenBucketLimiter::class);
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:user:'.$account->id, 15)->andReturnTrue();
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:app', 300)->andReturnTrue();

    Http::fake([
        sprintf('%s/2/dm_conversations/with/%s/messages', config('twitter.base_url'), '333') => Http::response([], 429, [
            'retry-after' => '120',
        ]),
    ]);

    $sender = new DirectMessageSender($limiter);

    $exception = null;

    try {
        $sender->send($account->fresh(), '333', 'Hi');
    } catch (RateLimitException $rateLimit) {
        $exception = $rateLimit;
    }

    expect($exception)->not->toBeNull();
    expect($exception->retryAfter)->toBe(120);
});

it('wraps failures from the Twitter API in a direct message exception', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $limiter = \Mockery::mock(TokenBucketLimiter::class);
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:user:'.$account->id, 15)->andReturnTrue();
    $limiter->shouldReceive('consumeKey')->once()->with('twitter:dm:app', 300)->andReturnTrue();

    Http::fake([
        sprintf('%s/2/dm_conversations/with/%s/messages', config('twitter.base_url'), '444') => Http::response([], 500),
    ]);

    $sender = new DirectMessageSender($limiter);

    expect(fn () => $sender->send($account->fresh(), '444', 'Oops'))
        ->toThrow(DirectMessageException::class);
});
