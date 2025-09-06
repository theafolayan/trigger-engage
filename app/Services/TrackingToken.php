<?php

declare(strict_types=1);

namespace App\Services;

class TrackingToken
{
    public function __construct(private readonly string $key)
    {
    }

    public function sign(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, $this->key);

        return $encoded.'.'.$signature;
    }

    public function verify(string $token): ?array
    {
        if (!str_contains($token, '.')) {
            return null;
        }

        [$encoded, $signature] = explode('.', $token, 2);
        $expected = hash_hmac('sha256', $encoded, $this->key);

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'));
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : null;
    }
}
