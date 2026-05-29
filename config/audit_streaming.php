<?php

return [
    'enabled' => env('AUDIT_STREAMING_ENABLED', false),

    'min_severity' => env('AUDIT_STREAMING_MIN_SEVERITY', 'CRITICAL'),

    'slack' => [
        'enabled' => env('AUDIT_STREAMING_SLACK_ENABLED', false),
        'webhook_url' => env('AUDIT_STREAMING_SLACK_WEBHOOK_URL'),
    ],

    'siem' => [
        'enabled' => env('AUDIT_STREAMING_SIEM_ENABLED', false),
        'endpoint' => env('AUDIT_STREAMING_SIEM_ENDPOINT'),
        'token' => env('AUDIT_STREAMING_SIEM_TOKEN'),
    ],

    'archive' => [
        'enabled' => env('AUDIT_STREAMING_ARCHIVE_ENABLED', false),
        'disk' => env('AUDIT_STREAMING_ARCHIVE_DISK', 's3'),
        'path' => env('AUDIT_STREAMING_ARCHIVE_PATH', 'uhms-audit'),
    ],

    'queue' => env('AUDIT_STREAMING_QUEUE', 'default'),

    'async_writes' => env('AUDIT_LOG_ASYNC', false),
];
