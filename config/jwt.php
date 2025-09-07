<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'access_ttl' => env('JWT_ACCESS_TTL', 900),
    'refresh_ttl' => env('JWT_REFRESH_TTL', 60 * 60 * 24 * 30),
];
