<?php

declare(strict_types=1);

use App\Jobs\Twitter\SendDirectMessage;
use App\Services\Twitter\DirectMessageSender;
use App\Services\Twitter\Exceptions\RateLimitException;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('delegates direct message sending to the twitter service when the account is active', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $job = new SendDirectMessage($account, '789', 'Welcome!');

    $sender = \Mockery::mock(DirectMessageSender::class);
    $sender->shouldReceive('send')
        ->once()
        ->with(\Mockery::on(fn ($freshAccount) => $freshAccount->is($account)), '789', 'Welcome!');

    $job->handle($sender);
});

it('releases the job with the provided retry delay when a rate limit is encountered', function () {
    $account = createTwitterAccountForPlanName('Pro');

    $job = new SendDirectMessage($account, '123', 'Hello');

    $queueJob = \Mockery::mock(QueueJob::class);
    $queueJob->shouldReceive('release')->once()->with(10);
    $job->setJob($queueJob);

    $sender = \Mockery::mock(DirectMessageSender::class);
    $sender->shouldReceive('send')
        ->once()
        ->andThrow(new RateLimitException('Rate limit hit', 10));

    $job->handle($sender);
});

it('does not attempt to send a direct message when the account is disconnected', function () {
    $account = createTwitterAccountForPlanName('Pro', [
        'disconnected_at' => now(),
    ]);

    $job = new SendDirectMessage($account, '456', 'Hello again');

    $sender = \Mockery::mock(DirectMessageSender::class);
    $sender->shouldReceive('send')->never();

    $job->handle($sender);
});
