<?php

declare(strict_types=1);

use App\Services\RateLimiter\TokenBucketLimiter;
use Predis\Client;

it('enforces per-minute caps', function (): void {
    $client = new Client();
    $client->flushdb();

    $limiter = new TokenBucketLimiter($client);
    $workspaceId = 999;

    expect($limiter->consume($workspaceId, 2))->toBeTrue();
    expect($limiter->consume($workspaceId, 2))->toBeTrue();
    expect($limiter->consume($workspaceId, 2))->toBeFalse();
});
