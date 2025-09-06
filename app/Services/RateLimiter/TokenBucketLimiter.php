<?php

declare(strict_types=1);

namespace App\Services\RateLimiter;

use Predis\Client;

class TokenBucketLimiter
{
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?? new Client([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
        ]);
    }

    public function consume(int $workspaceId, int $perMinute, int $tokens = 1): bool
    {
        $key = "rate:" . $workspaceId;
        $now = microtime(true);

        try {
            $stored = $this->client->get($key);
        } catch (\Throwable $e) {
            return true;
        }
        if ($stored === null) {
            $bucket = ['tokens' => $perMinute, 'time' => $now];
        } else {
            $bucket = json_decode($stored, true);
            $rate = $perMinute / 60;
            $elapsed = max(0, $now - ($bucket['time'] ?? $now));
            $bucket['tokens'] = min($perMinute, ($bucket['tokens'] ?? 0) + $elapsed * $rate);
            $bucket['time'] = $now;
        }

        if ($bucket['tokens'] < $tokens) {
            $this->client->setex($key, 60, json_encode($bucket));
            return false;
        }

        $bucket['tokens'] -= $tokens;
        $this->client->setex($key, 60, json_encode($bucket));

        return true;
    }
}
