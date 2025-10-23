<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for device fingerprinting
    | to prevent abuse and multiple account creation.
    |
    */

    'enabled' => env('DEVICE_FINGERPRINTING_ENABLED', true),
    'max_users_per_device' => env('DEVICE_FINGERPRINTING_MAX_USERS', 3),
    'device_block_duration' => env('DEVICE_FINGERPRINTING_BLOCK_DURATION', 7200), // 2 hours
    'redis_prefix' => env('DEVICE_FINGERPRINTING_REDIS_PREFIX', 'device_fingerprint'),
    'suspicious_threshold' => env('DEVICE_FINGERPRINTING_SUSPICIOUS_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Fingerprint Components
    |--------------------------------------------------------------------------
    |
    | Components used to generate device fingerprints
    |
    */

    'components' => [
        'user_agent' => true,
        'accept_language' => true,
        'accept_encoding' => true,
        'connection' => true,
        'dnt' => true,
        'accept' => true,
        'cache_control' => true,
        'pragma' => true,
        'ip_hash' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns that indicate suspicious device behavior
    |
    */

    'suspicious_patterns' => [
        'bot_user_agents' => [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
            '/phantom/i',
            '/headless/i',
        ],
        'missing_headers' => [
            'user_agent' => true,
            'accept_language' => true,
            'accept_encoding' => true,
        ],
        'rapid_requests' => [
            'max_per_minute' => env('DEVICE_FINGERPRINTING_MAX_REQUESTS_PER_MINUTE', 60),
            'max_per_hour' => env('DEVICE_FINGERPRINTING_MAX_REQUESTS_PER_HOUR', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Scoring
    |--------------------------------------------------------------------------
    |
    | Configuration for device risk scoring
    |
    */

    'risk_scoring' => [
        'multi_user_penalty' => env('DEVICE_FINGERPRINTING_MULTI_USER_PENALTY', 0.3),
        'high_request_penalty' => env('DEVICE_FINGERPRINTING_HIGH_REQUEST_PENALTY', 0.2),
        'suspicious_penalty' => env('DEVICE_FINGERPRINTING_SUSPICIOUS_PENALTY', 0.4),
        'blocked_penalty' => env('DEVICE_FINGERPRINTING_BLOCKED_PENALTY', 0.5),
        'max_risk_score' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for device blocking
    |
    */

    'blocking' => [
        'auto_block_threshold' => env('DEVICE_FINGERPRINTING_AUTO_BLOCK_THRESHOLD', 0.8),
        'manual_block_enabled' => env('DEVICE_FINGERPRINTING_MANUAL_BLOCK_ENABLED', true),
        'block_notification' => env('DEVICE_FINGERPRINTING_BLOCK_NOTIFICATION', true),
        'block_reasons' => [
            'multiple_accounts' => 'Multiple accounts detected from same device',
            'suspicious_behavior' => 'Suspicious device behavior detected',
            'rapid_requests' => 'Excessive request frequency detected',
            'bot_activity' => 'Bot-like activity detected',
            'manual_block' => 'Manually blocked by administrator',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring device fingerprinting events
    |
    */

    'monitoring' => [
        'log_all_events' => env('DEVICE_FINGERPRINTING_LOG_ALL_EVENTS', false),
        'log_suspicious_only' => env('DEVICE_FINGERPRINTING_LOG_SUSPICIOUS_ONLY', true),
        'alert_threshold' => env('DEVICE_FINGERPRINTING_ALERT_THRESHOLD', 10), // events per hour
        'alert_channels' => [
            'log' => true,
            'email' => env('DEVICE_FINGERPRINTING_ALERT_EMAIL', false),
            'slack' => env('DEVICE_FINGERPRINTING_ALERT_SLACK', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy and Compliance
    |--------------------------------------------------------------------------
    |
    | Configuration for privacy and compliance features
    |
    */

    'privacy' => [
        'hash_ip_addresses' => env('DEVICE_FINGERPRINTING_HASH_IP_ADDRESSES', true),
        'retention_period' => env('DEVICE_FINGERPRINTING_RETENTION_PERIOD', 30), // days
        'anonymize_after' => env('DEVICE_FINGERPRINTING_ANONYMIZE_AFTER', 90), // days
        'gdpr_compliant' => env('DEVICE_FINGERPRINTING_GDPR_COMPLIANT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features
    |--------------------------------------------------------------------------
    |
    | Configuration for advanced device fingerprinting features
    |
    */

    'advanced' => [
        'canvas_fingerprinting' => env('DEVICE_FINGERPRINTING_CANVAS_FINGERPRINTING', false),
        'webgl_fingerprinting' => env('DEVICE_FINGERPRINTING_WEBGL_FINGERPRINTING', false),
        'audio_fingerprinting' => env('DEVICE_FINGERPRINTING_AUDIO_FINGERPRINTING', false),
        'battery_fingerprinting' => env('DEVICE_FINGERPRINTING_BATTERY_FINGERPRINTING', false),
        'timezone_fingerprinting' => env('DEVICE_FINGERPRINTING_TIMEZONE_FINGERPRINTING', true),
        'screen_fingerprinting' => env('DEVICE_FINGERPRINTING_SCREEN_FINGERPRINTING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization
    |
    */

    'performance' => [
        'cache_ttl' => env('DEVICE_FINGERPRINTING_CACHE_TTL', 3600), // 1 hour
        'cleanup_interval' => env('DEVICE_FINGERPRINTING_CLEANUP_INTERVAL', 3600), // 1 hour
        'batch_size' => env('DEVICE_FINGERPRINTING_BATCH_SIZE', 100),
        'async_processing' => env('DEVICE_FINGERPRINTING_ASYNC_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing and Development
    |--------------------------------------------------------------------------
    |
    | Configuration for testing and development environments
    |
    */

    'testing' => [
        'mock_fingerprints' => env('DEVICE_FINGERPRINTING_MOCK_FINGERPRINTS', false),
        'test_mode' => env('DEVICE_FINGERPRINTING_TEST_MODE', false),
        'debug_logging' => env('DEVICE_FINGERPRINTING_DEBUG_LOGGING', false),
    ],
];
