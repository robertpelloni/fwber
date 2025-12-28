<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Advanced Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('FEATURE_RATE_LIMITS', true),
    
    'redis_prefix' => 'rate_limit',
    
    'cleanup_interval' => 3600, // 1 hour
    
    'default_capacity' => 60,
    
    'default_refill_rate' => 1, // tokens per second

    'actions' => [
        'api_call' => [
            'capacity' => env('RATE_LIMIT_API_CALL_LIMIT', 60),
            'refill_rate' => 1,
            'cost_per_request' => 1,
            'burst_allowance' => 10,
        ],
        'auth_attempt' => [
            'capacity' => env('RATE_LIMIT_AUTH_ATTEMPT_LIMIT', 5),
            'refill_rate' => 0.0033, // ~1 token per 5 minutes (1/300)
            'cost_per_request' => 1,
            'burst_allowance' => 1,
        ],
        'content_generation' => [
            'capacity' => 10,
            'refill_rate' => 0.1, // 1 token every 10 seconds
            'cost_per_request' => 1,
            'burst_allowance' => 2,
        ],
        'photo_upload' => [
            'capacity' => 10,
            'refill_rate' => 0.016, // ~1 token per minute
            'cost_per_request' => 1,
            'burst_allowance' => 2,
        ],
    ],
];
