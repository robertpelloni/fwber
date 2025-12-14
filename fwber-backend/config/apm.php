<?php

return [
    'enabled' => env('APM_ENABLED', false),
    'service_name' => env('APM_SERVICE_NAME', 'fwber-backend'),
    'environment' => env('APP_ENV', 'production'),
    
    // Threshold in milliseconds to log slow requests
    'slow_request_threshold' => env('APM_SLOW_REQUEST_THRESHOLD', 1000),

    // Alerting Configuration
    'alert_threshold_ms' => env('APM_ALERT_THRESHOLD_MS', 2000), // Alert if average duration exceeds this
    'alert_min_occurrences' => env('APM_ALERT_MIN_OCCURRENCES', 5), // Minimum occurrences to trigger alert
    'alert_email' => env('APM_ALERT_EMAIL', 'admin@fwber.me'),

    // Datadog Configuration (Placeholder)
    'datadog' => [
        'api_key' => env('DD_API_KEY'),
        'app_key' => env('DD_APP_KEY'),
        'host' => env('DD_AGENT_HOST', 'localhost'),
        'port' => env('DD_AGENT_PORT', 8126),
    ],
];
