<?php

declare(strict_types=1);

use App\Jobs\Twitter\PollFollowers;
use App\Jobs\Twitter\SendDirectMessage;
use App\Models\TwitterFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('persists new followers and dispatches direct message jobs', function () {
    Queue::fake();
    $now = Carbon::parse('2025-01-01 00:00:00');
    Carbon::setTestNow($now);

    config(['twitter.dm.welcome_message' => 'Hello follower!']);

    $account = createTwitterAccountForPlanName('Pro');

    Http::fake([
        sprintf('%s/2/users/%s/followers*', config('twitter.base_url'), $account->twitter_id) => Http::response([
            'data' => [
                [
                    'id' => '111',
                    'username' => 'first-user',
                    'name' => 'First User',
                ],
            ],
        ], 200),
    ]);

    (new PollFollowers($account))->handle();

    $follower = TwitterFollower::query()
        ->where('twitter_account_id', $account->id)
        ->first();

    expect($follower)->not->toBeNull();
    expect($follower->follower_id)->toBe('111');
    expect($follower->username)->toBe('first-user');
    expect($follower->name)->toBe('First User');
    expect($follower->seen_at?->diffInSeconds($now))->toBeLessThan(1);

    expect($account->fresh()->last_polled_at?->diffInSeconds($now))->toBeLessThan(1);

    Queue::assertPushedOn(config('twitter.queues.dm_queue'), SendDirectMessage::class, function ($job) use ($account) {
        return $job->twitterAccount->is($account)
            && $job->participantId === '111'
            && $job->message === 'Hello follower!';
    });

    Queue::assertPushed(SendDirectMessage::class, 1);

    Carbon::setTestNow();
});

it('does not dispatch direct messages for followers already seen', function () {
    Queue::fake();
    $now = Carbon::parse('2025-01-01 00:00:00');
    Carbon::setTestNow($now);

    $account = createTwitterAccountForPlanName('Pro');

    TwitterFollower::create([
        'twitter_account_id' => $account->id,
        'follower_id' => '222',
        'username' => 'existing',
        'name' => 'Existing User',
        'seen_at' => now()->subDay(),
    ]);

    Http::fake([
        sprintf('%s/2/users/%s/followers*', config('twitter.base_url'), $account->twitter_id) => Http::response([
            'data' => [
                [
                    'id' => '222',
                    'username' => 'existing',
                    'name' => 'Existing User',
                ],
            ],
        ], 200),
    ]);

    (new PollFollowers($account))->handle();

    Queue::assertNotPushed(SendDirectMessage::class);
    expect(TwitterFollower::query()->count())->toBe(1);
    expect($account->fresh()->last_polled_at?->diffInSeconds($now))->toBeLessThan(1);

    Carbon::setTestNow();
});

it('paginates through all follower pages to welcome each new follower', function () {
    Queue::fake();
    config(['twitter.dm.welcome_message' => 'Welcome aboard!']);

    $account = createTwitterAccountForPlanName('Pro');

    Http::fake([
        sprintf('%s/2/users/%s/followers*', config('twitter.base_url'), $account->twitter_id) => Http::sequence()
            ->push([
                'data' => [
                    [
                        'id' => '333',
                        'username' => 'first-page-user',
                        'name' => 'First Page User',
                    ],
                ],
                'meta' => [
                    'next_token' => 'NEXT_TOKEN',
                ],
            ], 200)
            ->push([
                'data' => [
                    [
                        'id' => '444',
                        'username' => 'second-page-user',
                        'name' => 'Second Page User',
                    ],
                ],
            ], 200),
    ]);

    (new PollFollowers($account))->handle();

    $followerIds = TwitterFollower::query()
        ->where('twitter_account_id', $account->id)
        ->pluck('follower_id')
        ->all();

    expect($followerIds)->toBe(['333', '444']);

    $dmQueue = config('twitter.queues.dm_queue');

    Queue::assertPushed(SendDirectMessage::class, 2);

    Queue::assertPushedOn($dmQueue, SendDirectMessage::class, function (SendDirectMessage $job) use ($account) {
        return $job->twitterAccount->is($account)
            && $job->participantId === '333'
            && $job->message === 'Welcome aboard!';
    });

    Queue::assertPushedOn($dmQueue, SendDirectMessage::class, function (SendDirectMessage $job) use ($account) {
        return $job->twitterAccount->is($account)
            && $job->participantId === '444'
            && $job->message === 'Welcome aboard!';
    });
});
