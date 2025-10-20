<?php

return [
    'base_url' => rtrim(env('TWITTER_API_BASE_URL', 'https://api.x.com'), '/'),

    'polling' => [
        'page_size' => (int) env('TWITTER_FOLLOWER_PAGE_SIZE', 100),
        'plans' => [
            'Free' => (int) env('TWITTER_POLL_INTERVAL_FREE', 30),
            'Pro' => (int) env('TWITTER_POLL_INTERVAL_PRO', 10),
            'Enterprise' => (int) env('TWITTER_POLL_INTERVAL_ENTERPRISE', 5),
        ],
    ],

    'queues' => [
        'connection' => env('TWITTER_QUEUE_CONNECTION', 'redis'),
        'poll_queue' => env('TWITTER_POLL_QUEUE', 'twitter-polling'),
        'dm_queue' => env('TWITTER_DM_QUEUE', 'twitter-dm'),
    ],

    'dm' => [
        'welcome_message' => env('TWITTER_WELCOME_MESSAGE', 'Thanks for following us on X!'),
        'backoff' => [
            'attempts' => (int) env('TWITTER_DM_ATTEMPTS', 3),
            'initial_seconds' => (int) env('TWITTER_DM_INITIAL_BACKOFF', 2),
            'default_retry_after' => (int) env('TWITTER_DM_DEFAULT_RETRY_AFTER', 60),
        ],
        'rate_limits' => [
            'per_user' => [
                'key_prefix' => 'twitter:dm:user:',
                'per_minute' => (int) env('TWITTER_DM_PER_USER_PER_MINUTE', 15),
            ],
            'per_app' => [
                'key' => 'twitter:dm:app',
                'per_minute' => (int) env('TWITTER_DM_PER_APP_PER_MINUTE', 300),
            ],
        ],
    ],
];
