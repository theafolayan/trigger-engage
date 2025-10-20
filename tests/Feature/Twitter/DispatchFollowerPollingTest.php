<?php

declare(strict_types=1);

use App\Jobs\Twitter\DispatchFollowerPolling;
use App\Jobs\Twitter\PollFollowers;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches follower polling jobs for accounts on the matching plan with access tokens', function () {
    Queue::fake();

    $plan = Plan::factory()->create(['name' => 'Pro']);
    $matchingAccount = createTwitterAccountForPlan($plan);
    $withoutToken = createTwitterAccountForPlan($plan, [
        'access_token' => '',
    ]);
    $otherPlanAccount = createTwitterAccountForPlanName('Free');

    (new DispatchFollowerPolling('Pro'))->handle();

    Queue::assertPushedOn(config('twitter.queues.poll_queue'), PollFollowers::class, function ($job) use ($matchingAccount) {
        return $job->twitterAccount->is($matchingAccount)
            && $job->connection === config('twitter.queues.connection')
            && $job->queue === config('twitter.queues.poll_queue');
    });

    Queue::assertNotPushed(PollFollowers::class, function ($job) use ($withoutToken) {
        return $job->twitterAccount->is($withoutToken);
    });

    Queue::assertNotPushed(PollFollowers::class, function ($job) use ($otherPlanAccount) {
        return $job->twitterAccount->is($otherPlanAccount);
    });

    Queue::assertPushed(PollFollowers::class, 1);
});
