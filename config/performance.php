<?php

$environment = env('APP_ENV', 'production');
$isLocalOrTesting = in_array($environment, ['local', 'testing'], true);

return [
    'profiling' => [
        'enabled' => env('PERFORMANCE_PROFILING_ENABLED', $isLocalOrTesting),
        'response_headers' => env('PERFORMANCE_RESPONSE_HEADERS', $environment === 'local'),
        'warning_query_count' => (int) env('PERFORMANCE_QUERY_WARNING_COUNT', 100),
        'slow_query_ms' => (float) env('PERFORMANCE_SLOW_QUERY_MS', 100),
    ],

    'lazy_loading' => [
        'detect' => env('PERFORMANCE_DETECT_LAZY_LOADING', $isLocalOrTesting),
        'log' => env('PERFORMANCE_LOG_LAZY_LOADING', $environment === 'local'),
    ],
];
