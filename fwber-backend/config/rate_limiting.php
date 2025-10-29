<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the advanced rate limiting
    | system used to prevent abuse and protect API costs.
    |
    */

    'default_capacity' => env('RATE_LIMIT_DEFAULT_CAPACITY', 100),
    'default_refill_rate' => env('RATE_LIMIT_DEFAULT_REFILL_RATE', 10),
    'redis_prefix' => env('RATE_LIMIT_REDIS_PREFIX', 'rate_limit'),
    'cleanup_interval' => env('RATE_LIMIT_CLEANUP_INTERVAL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Action-Specific Rate Limits
    |--------------------------------------------------------------------------
    |
    | Define rate limits for specific actions. Each action can have:
    | - capacity: Maximum tokens in bucket
    | - refill_rate: Tokens added per second
    | - cost_per_request: Tokens consumed per request
    | - burst_allowance: Extra tokens for burst requests
    |
    */

    'actions' => [
        'content_generation' => [
            'capacity' => env('RATE_LIMIT_CONTENT_GENERATION_CAPACITY', 10),
            'refill_rate' => env('RATE_LIMIT_CONTENT_GENERATION_REFILL', 1),
            'cost_per_request' => env('RATE_LIMIT_CONTENT_GENERATION_COST', 1),
            'burst_allowance' => env('RATE_LIMIT_CONTENT_GENERATION_BURST', 5),
        ],

        'bulletin_post' => [
            'capacity' => env('RATE_LIMIT_BULLETIN_POST_CAPACITY', 20),
            'refill_rate' => env('RATE_LIMIT_BULLETIN_POST_REFILL', 2),
            'cost_per_request' => env('RATE_LIMIT_BULLETIN_POST_COST', 1),
            'burst_allowance' => env('RATE_LIMIT_BULLETIN_POST_BURST', 10),
        ],

        'location_update' => [
            'capacity' => env('RATE_LIMIT_LOCATION_UPDATE_CAPACITY', 50),
            'refill_rate' => env('RATE_LIMIT_LOCATION_UPDATE_REFILL', 5),
            'cost_per_request' => env('RATE_LIMIT_LOCATION_UPDATE_COST', 1),
            'burst_allowance' => env('RATE_LIMIT_LOCATION_UPDATE_BURST', 25),
        ],

        'photo_upload' => [
            'capacity' => env('RATE_LIMIT_PHOTO_UPLOAD_CAPACITY', 15),
            'refill_rate' => env('RATE_LIMIT_PHOTO_UPLOAD_REFILL', 1),
            'cost_per_request' => env('RATE_LIMIT_PHOTO_UPLOAD_COST', 2),
            'burst_allowance' => env('RATE_LIMIT_PHOTO_UPLOAD_BURST', 5),
        ],

        'api_call' => [
            'capacity' => env('RATE_LIMIT_API_CALL_CAPACITY', 1000),
            'refill_rate' => env('RATE_LIMIT_API_CALL_REFILL', 100),
            'cost_per_request' => env('RATE_LIMIT_API_CALL_COST', 1),
            'burst_allowance' => env('RATE_LIMIT_API_CALL_BURST', 200),
        ],

        'auth_attempt' => [
            'capacity' => env('RATE_LIMIT_AUTH_ATTEMPT_CAPACITY', 5),
            'refill_rate' => env('RATE_LIMIT_AUTH_ATTEMPT_REFILL', 1),
            'cost_per_request' => env('RATE_LIMIT_AUTH_ATTEMPT_COST', 1),
            'burst_allowance' => env('RATE_LIMIT_AUTH_ATTEMPT_BURST', 2),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Activity Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting suspicious rate limiting patterns
    |
    */

    'suspicious_activity' => [
        'max_rate_limited_actions' => env('RATE_LIMIT_MAX_RATE_LIMITED_ACTIONS', 3),
        'max_hits_per_minute' => env('RATE_LIMIT_MAX_HITS_PER_MINUTE', 10),
        'suspicious_threshold' => env('RATE_LIMIT_SUSPICIOUS_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP-Based Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Additional rate limiting based on IP address to prevent
    | distributed attacks
    |
    */

    'ip_limits' => [
        'enabled' => env('RATE_LIMIT_IP_ENABLED', true),
        'capacity' => env('RATE_LIMIT_IP_CAPACITY', 1000),
        'refill_rate' => env('RATE_LIMIT_IP_REFILL', 100),
        'block_duration' => env('RATE_LIMIT_IP_BLOCK_DURATION', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting
    |--------------------------------------------------------------------------
    |
    | Rate limiting based on device fingerprinting to prevent
    | multiple account abuse
    |
    */

    'device_fingerprinting' => [
        'enabled' => env('RATE_LIMIT_DEVICE_FINGERPRINTING_ENABLED', true),
        'max_users_per_device' => env('RATE_LIMIT_MAX_USERS_PER_DEVICE', 3),
        'device_block_duration' => env('RATE_LIMIT_DEVICE_BLOCK_DURATION', 7200), // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring rate limit events and alerting
    |
    */

    'monitoring' => [
        'log_all_events' => env('RATE_LIMIT_LOG_ALL_EVENTS', false),
        'log_suspicious_only' => env('RATE_LIMIT_LOG_SUSPICIOUS_ONLY', true),
        'alert_threshold' => env('RATE_LIMIT_ALERT_THRESHOLD', 100), // events per hour
        'alert_channels' => [
            'log' => true,
            'email' => env('RATE_LIMIT_ALERT_EMAIL', false),
            'slack' => env('RATE_LIMIT_ALERT_SLACK', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost-Based Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting based on estimated costs of operations
    |
    */

    'cost_based' => [
        'enabled' => env('RATE_LIMIT_COST_BASED_ENABLED', true),
        'daily_cost_limit' => env('RATE_LIMIT_DAILY_COST_LIMIT', 10.00), // $10 per user per day
        'cost_per_token' => env('RATE_LIMIT_COST_PER_TOKEN', 0.001), // $0.001 per token
        'emergency_stop_threshold' => env('RATE_LIMIT_EMERGENCY_STOP_THRESHOLD', 50.00), // $50
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits that adapt based on user behavior and system load
    |
    */

    'adaptive' => [
        'enabled' => env('RATE_LIMIT_ADAPTIVE_ENABLED', true),
        'trust_score_weight' => env('RATE_LIMIT_TRUST_SCORE_WEIGHT', 0.3),
        'system_load_weight' => env('RATE_LIMIT_SYSTEM_LOAD_WEIGHT', 0.2),
        'user_behavior_weight' => env('RATE_LIMIT_USER_BEHAVIOR_WEIGHT', 0.5),
        'min_capacity_multiplier' => env('RATE_LIMIT_MIN_CAPACITY_MULTIPLIER', 0.1),
        'max_capacity_multiplier' => env('RATE_LIMIT_MAX_CAPACITY_MULTIPLIER', 2.0),
    ],
];
