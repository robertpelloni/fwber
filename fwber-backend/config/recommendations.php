<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recommendation System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the AI-powered recommendation
    | system, including provider settings, caching, and algorithm parameters.
    |
    */

    'enabled' => env('RECOMMENDATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Configure which AI providers to use for recommendations. Multiple
    | providers can be used for consensus-based recommendations.
    |
    */
    'providers' => [
        'openai' => [
            'enabled' => env('OPENAI_RECOMMENDATIONS_ENABLED', true),
            'model' => env('OPENAI_RECOMMENDATION_MODEL', 'gpt-4'),
            'max_tokens' => env('OPENAI_RECOMMENDATION_MAX_TOKENS', 1000),
            'temperature' => env('OPENAI_RECOMMENDATION_TEMPERATURE', 0.7),
        ],
        'gemini' => [
            'enabled' => env('GEMINI_RECOMMENDATIONS_ENABLED', true),
            'model' => env('GEMINI_RECOMMENDATION_MODEL', 'gemini-pro'),
            'max_tokens' => env('GEMINI_RECOMMENDATION_MAX_TOKENS', 1000),
            'temperature' => env('GEMINI_RECOMMENDATION_TEMPERATURE', 0.7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for recommendations to improve performance and reduce
    | API costs. Recommendations are cached per user and context.
    |
    */
    'cache' => [
        'enabled' => env('RECOMMENDATIONS_CACHE_ENABLED', true),
        'ttl' => env('RECOMMENDATIONS_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('RECOMMENDATIONS_CACHE_PREFIX', 'recommendations'),
        'store' => env('RECOMMENDATIONS_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Algorithm Parameters
    |--------------------------------------------------------------------------
    |
    | Configure the recommendation algorithm parameters for different
    | recommendation types and scoring.
    |
    */
    'algorithm' => [
        'max_recommendations' => env('RECOMMENDATIONS_MAX_COUNT', 10),
        'diversity_factor' => env('RECOMMENDATIONS_DIVERSITY_FACTOR', 0.3),
        'freshness_weight' => env('RECOMMENDATIONS_FRESHNESS_WEIGHT', 0.4),
        'popularity_weight' => env('RECOMMENDATIONS_POPULARITY_WEIGHT', 0.3),
        'personal_weight' => env('RECOMMENDATIONS_PERSONAL_WEIGHT', 0.3),
        'location_weight' => env('RECOMMENDATIONS_LOCATION_WEIGHT', 0.2),
        'collaborative_weight' => env('RECOMMENDATIONS_COLLABORATIVE_WEIGHT', 0.4),
        'content_weight' => env('RECOMMENDATIONS_CONTENT_WEIGHT', 0.3),
        'ai_weight' => env('RECOMMENDATIONS_AI_WEIGHT', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types
    |--------------------------------------------------------------------------
    |
    | Define the types of content that can be recommended and their
    | configuration parameters.
    |
    */
    'content_types' => [
        'bulletin_board' => [
            'enabled' => true,
            'weight' => 1.0,
            'max_age_days' => 30,
            'min_activity_score' => 0.1,
        ],
        'bulletin_message' => [
            'enabled' => true,
            'weight' => 0.8,
            'max_age_days' => 7,
            'min_activity_score' => 0.2,
        ],
        'user_profile' => [
            'enabled' => true,
            'weight' => 0.6,
            'max_age_days' => 90,
            'min_activity_score' => 0.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Location-Based Recommendations
    |--------------------------------------------------------------------------
    |
    | Configure location-based recommendation parameters for proximity
    | and geographic relevance.
    |
    */
    'location' => [
        'enabled' => env('LOCATION_RECOMMENDATIONS_ENABLED', true),
        'default_radius' => env('LOCATION_DEFAULT_RADIUS', 5000), // meters
        'max_radius' => env('LOCATION_MAX_RADIUS', 50000), // meters
        'min_radius' => env('LOCATION_MIN_RADIUS', 100), // meters
        'radius_decay_factor' => env('LOCATION_RADIUS_DECAY', 0.1),
        'geohash_precision' => env('LOCATION_GEOHASH_PRECISION', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | A/B Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure A/B testing for recommendation algorithms to optimize
    | performance and user engagement.
    |
    */
    'ab_testing' => [
        'enabled' => env('RECOMMENDATIONS_AB_TESTING_ENABLED', true),
        'experiments' => [
            'algorithm_variants' => [
                'control' => 'hybrid',
                'variant_a' => 'ai_heavy',
                'variant_b' => 'location_heavy',
            ],
            'personalization_levels' => [
                'basic' => 0.3,
                'moderate' => 0.5,
                'aggressive' => 0.8,
            ],
        ],
        'traffic_split' => [
            'control' => 0.5,
            'variant_a' => 0.25,
            'variant_b' => 0.25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring and metrics collection for the
    | recommendation system.
    |
    */
    'monitoring' => [
        'enabled' => env('RECOMMENDATIONS_MONITORING_ENABLED', true),
        'metrics' => [
            'response_time' => true,
            'cache_hit_rate' => true,
            'api_calls' => true,
            'error_rate' => true,
            'user_satisfaction' => true,
        ],
        'alerts' => [
            'response_time_threshold' => 2000, // ms
            'error_rate_threshold' => 0.05, // 5%
            'cache_hit_rate_threshold' => 0.8, // 80%
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy and Compliance
    |--------------------------------------------------------------------------
    |
    | Configure privacy settings and compliance requirements for the
    | recommendation system.
    |
    */
    'privacy' => [
        'data_retention_days' => env('RECOMMENDATIONS_DATA_RETENTION_DAYS', 90),
        'anonymize_user_data' => env('RECOMMENDATIONS_ANONYMIZE_DATA', true),
        'consent_required' => env('RECOMMENDATIONS_CONSENT_REQUIRED', true),
        'opt_out_allowed' => env('RECOMMENDATIONS_OPT_OUT_ALLOWED', true),
        'data_export_enabled' => env('RECOMMENDATIONS_DATA_EXPORT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for recommendation API endpoints to prevent
    | abuse and ensure fair resource usage.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RECOMMENDATIONS_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('RECOMMENDATIONS_RATE_LIMIT', 60),
        'burst_limit' => env('RECOMMENDATIONS_BURST_LIMIT', 10),
        'cooldown_period' => env('RECOMMENDATIONS_COOLDOWN_PERIOD', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Configure fallback behavior when AI providers are unavailable or
    | recommendations fail to generate.
    |
    */
    'fallback' => [
        'enabled' => env('RECOMMENDATIONS_FALLBACK_ENABLED', true),
        'strategy' => env('RECOMMENDATIONS_FALLBACK_STRATEGY', 'popular'), // popular, recent, random
        'max_fallback_items' => env('RECOMMENDATIONS_FALLBACK_MAX_ITEMS', 5),
        'fallback_timeout' => env('RECOMMENDATIONS_FALLBACK_TIMEOUT', 5000), // ms
    ],
];
