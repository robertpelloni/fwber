<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for security monitoring
    | and threat detection.
    |
    */

    'enabled' => env('SECURITY_MONITORING_ENABLED', true),
    'log_all_events' => env('SECURITY_MONITORING_LOG_ALL_EVENTS', false),
    'alert_threshold' => env('SECURITY_MONITORING_ALERT_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Alert Channels
    |--------------------------------------------------------------------------
    |
    | Configuration for security alert channels
    |
    */

    'alert_channels' => [
        'log' => env('SECURITY_MONITORING_LOG_ALERTS', true),
        'email' => env('SECURITY_MONITORING_EMAIL_ALERTS', false),
        'slack' => env('SECURITY_MONITORING_SLACK_ALERTS', false),
        'webhook' => env('SECURITY_MONITORING_WEBHOOK_ALERTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for email alerts
    |
    */

    'email' => [
        'recipients' => env('SECURITY_MONITORING_EMAIL_RECIPIENTS', ''),
        'from_address' => env('SECURITY_MONITORING_EMAIL_FROM', 'security@fwber.me'),
        'from_name' => env('SECURITY_MONITORING_EMAIL_FROM_NAME', 'FWBer Security'),
        'subject_prefix' => env('SECURITY_MONITORING_EMAIL_SUBJECT_PREFIX', '[SECURITY]'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Slack alerts
    |
    */

    'slack' => [
        'webhook_url' => env('SECURITY_MONITORING_SLACK_WEBHOOK_URL'),
        'channel' => env('SECURITY_MONITORING_SLACK_CHANNEL', '#security'),
        'username' => env('SECURITY_MONITORING_SLACK_USERNAME', 'Security Bot'),
        'icon_emoji' => env('SECURITY_MONITORING_SLACK_ICON', ':warning:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook alerts
    |
    */

    'webhook' => [
        'url' => env('SECURITY_MONITORING_WEBHOOK_URL'),
        'secret' => env('SECURITY_MONITORING_WEBHOOK_SECRET'),
        'timeout' => env('SECURITY_MONITORING_WEBHOOK_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configuration for alert thresholds
    |
    */

    'thresholds' => [
        'failed_logins' => env('SECURITY_MONITORING_FAILED_LOGINS_THRESHOLD', 5),
        'rate_limit_hits' => env('SECURITY_MONITORING_RATE_LIMIT_HITS_THRESHOLD', 10),
        'suspicious_activity' => env('SECURITY_MONITORING_SUSPICIOUS_ACTIVITY_THRESHOLD', 3),
        'content_moderation_flags' => env('SECURITY_MONITORING_CONTENT_MODERATION_FLAGS_THRESHOLD', 20),
        'device_fingerprint_alerts' => env('SECURITY_MONITORING_DEVICE_FINGERPRINT_ALERTS_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Types
    |--------------------------------------------------------------------------
    |
    | Configuration for different event types
    |
    */

    'event_types' => [
        'auth_failed' => [
            'severity' => 'medium',
            'alert_threshold' => 5,
            'time_window' => '5m',
        ],
        'auth_success' => [
            'severity' => 'low',
            'alert_threshold' => 0,
            'time_window' => '1h',
        ],
        'rate_limit_exceeded' => [
            'severity' => 'medium',
            'alert_threshold' => 10,
            'time_window' => '1h',
        ],
        'content_moderation' => [
            'severity' => 'high',
            'alert_threshold' => 20,
            'time_window' => '1h',
        ],
        'device_fingerprint' => [
            'severity' => 'high',
            'alert_threshold' => 5,
            'time_window' => '1h',
        ],
        'location_update' => [
            'severity' => 'low',
            'alert_threshold' => 0,
            'time_window' => '1h',
        ],
        'api_call' => [
            'severity' => 'low',
            'alert_threshold' => 100,
            'time_window' => '5m',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pattern Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for pattern detection
    |
    */

    'pattern_detection' => [
        'multiple_failed_logins' => [
            'enabled' => true,
            'threshold' => 5,
            'time_window' => '5m',
            'severity' => 'high',
        ],
        'unusual_location_access' => [
            'enabled' => true,
            'distance_threshold' => 1000, // km
            'time_threshold' => 1, // hours
            'severity' => 'medium',
        ],
        'rapid_api_calls' => [
            'enabled' => true,
            'threshold' => 100,
            'time_window' => '5m',
            'severity' => 'medium',
        ],
        'suspicious_content' => [
            'enabled' => true,
            'severity_threshold' => 'high',
            'severity' => 'high',
        ],
        'device_abuse' => [
            'enabled' => true,
            'suspicious_threshold' => true,
            'severity' => 'high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configuration for data retention
    |
    */

    'data_retention' => [
        'events_retention_days' => env('SECURITY_MONITORING_EVENTS_RETENTION_DAYS', 90),
        'alerts_retention_days' => env('SECURITY_MONITORING_ALERTS_RETENTION_DAYS', 365),
        'cleanup_interval' => env('SECURITY_MONITORING_CLEANUP_INTERVAL', 24), // hours
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
        'batch_size' => env('SECURITY_MONITORING_BATCH_SIZE', 100),
        'async_processing' => env('SECURITY_MONITORING_ASYNC_PROCESSING', true),
        'cache_ttl' => env('SECURITY_MONITORING_CACHE_TTL', 3600), // 1 hour
        'queue_name' => env('SECURITY_MONITORING_QUEUE_NAME', 'security'),
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
        'mock_events' => env('SECURITY_MONITORING_MOCK_EVENTS', false),
        'test_mode' => env('SECURITY_MONITORING_TEST_MODE', false),
        'debug_logging' => env('SECURITY_MONITORING_DEBUG_LOGGING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance and Privacy
    |--------------------------------------------------------------------------
    |
    | Configuration for compliance and privacy features
    |
    */

    'compliance' => [
        'gdpr_compliant' => env('SECURITY_MONITORING_GDPR_COMPLIANT', true),
        'anonymize_ip_addresses' => env('SECURITY_MONITORING_ANONYMIZE_IP_ADDRESSES', true),
        'encrypt_sensitive_data' => env('SECURITY_MONITORING_ENCRYPT_SENSITIVE_DATA', true),
        'audit_log_enabled' => env('SECURITY_MONITORING_AUDIT_LOG_ENABLED', true),
    ],
];
