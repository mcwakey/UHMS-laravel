<?php

return [
    'enabled' => env('NOTIFICATIONS_ENABLED', true),

    'dedupe_minutes' => env('NOTIFICATIONS_DEDUPE_MINUTES', 15),

    'latest_limit' => env('NOTIFICATIONS_LATEST_LIMIT', 10),

    'poll_interval_seconds' => env('NOTIFICATIONS_POLL_INTERVAL', 30),

    'cleanup' => [
        'read_retention_days' => env('NOTIFICATIONS_READ_RETENTION_DAYS', 30),
        'all_retention_days' => env('NOTIFICATIONS_ALL_RETENTION_DAYS', 180),
    ],
];
