<?php

declare(strict_types=1);

use App\Services\RateLimiter\TokenBucketLimiter;
it('enforces per-minute caps', function (): void {
    $client = new class {
        public array $store = [];
        public function get(string $key): ?string { return $this->store[$key] ?? null; }
        public function setex(string $key, int $ttl, string $value): void { $this->store[$key] = $value; }
        public function flushdb(): void { $this->store = []; }
    };

    $client->flushdb();

    $limiter = new TokenBucketLimiter($client);
    $workspaceId = 999;

    expect($limiter->consume($workspaceId, 2))->toBeTrue();
    expect($limiter->consume($workspaceId, 2))->toBeTrue();
    expect($limiter->consume($workspaceId, 2))->toBeFalse();
});
