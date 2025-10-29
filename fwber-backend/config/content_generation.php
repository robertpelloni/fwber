<?php

return [
    'enabled' => env('CONTENT_GENERATION_ENABLED', true),
    'providers' => explode(',', env('CONTENT_GENERATION_PROVIDERS', 'openai,gemini')),
    'max_tokens' => env('CONTENT_GENERATION_MAX_TOKENS', 1000),
    'temperature' => env('CONTENT_GENERATION_TEMPERATURE', 0.7),
    'cache_ttl' => env('CONTENT_GENERATION_CACHE_TTL', 3600), // 1 hour
    'safety_threshold' => env('CONTENT_GENERATION_SAFETY_THRESHOLD', 0.8),
    'rate_limits' => [
        'profile_generation' => env('PROFILE_GENERATION_RATE_LIMIT', 10), // per hour
        'post_suggestions' => env('POST_SUGGESTIONS_RATE_LIMIT', 20), // per hour
        'conversation_starters' => env('CONVERSATION_STARTERS_RATE_LIMIT', 15), // per hour
        'content_optimization' => env('CONTENT_OPTIMIZATION_RATE_LIMIT', 30), // per hour
    ],
    'quality_thresholds' => [
        'min_readability' => env('MIN_READABILITY_SCORE', 0.5),
        'min_engagement' => env('MIN_ENGAGEMENT_SCORE', 0.3),
        'min_clarity' => env('MIN_CLARITY_SCORE', 0.4),
        'max_length' => env('MAX_CONTENT_LENGTH', 2000),
        'min_length' => env('MIN_CONTENT_LENGTH', 10),
    ],
    'personality_analysis' => [
        'enabled' => env('PERSONALITY_ANALYSIS_ENABLED', true),
        'min_content_length' => env('PERSONALITY_MIN_CONTENT_LENGTH', 50),
        'traits' => [
            'extroversion',
            'openness',
            'conscientiousness',
            'agreeableness',
            'neuroticism',
        ],
    ],
    'content_types' => [
        'profile' => [
            'max_suggestions' => 5,
            'min_confidence' => 0.6,
            'safety_required' => true,
        ],
        'post_suggestions' => [
            'max_suggestions' => 5,
            'min_confidence' => 0.5,
            'safety_required' => true,
        ],
        'conversation_starters' => [
            'max_suggestions' => 3,
            'min_confidence' => 0.7,
            'safety_required' => true,
        ],
    ],
    'optimization' => [
        'enabled' => env('CONTENT_OPTIMIZATION_ENABLED', true),
        'types' => [
            'engagement' => [
                'weight' => 0.4,
                'enabled' => true,
            ],
            'clarity' => [
                'weight' => 0.3,
                'enabled' => true,
            ],
            'safety' => [
                'weight' => 0.2,
                'enabled' => true,
            ],
            'relevance' => [
                'weight' => 0.1,
                'enabled' => true,
            ],
        ],
        'min_improvement_score' => env('MIN_OPTIMIZATION_IMPROVEMENT', 0.1),
        'max_iterations' => env('MAX_OPTIMIZATION_ITERATIONS', 3),
    ],
    'analytics' => [
        'enabled' => env('CONTENT_ANALYTICS_ENABLED', true),
        'track_generations' => env('TRACK_GENERATIONS', true),
        'track_optimizations' => env('TRACK_OPTIMIZATIONS', true),
        'track_feedback' => env('TRACK_FEEDBACK', true),
        'retention_days' => env('ANALYTICS_RETENTION_DAYS', 90),
    ],
    'feedback' => [
        'enabled' => env('CONTENT_FEEDBACK_ENABLED', true),
        'min_rating' => 1,
        'max_rating' => 5,
        'feedback_required' => env('FEEDBACK_REQUIRED', false),
        'improvement_suggestions' => env('IMPROVEMENT_SUGGESTIONS_ENABLED', true),
    ],
    'caching' => [
        'enabled' => env('CONTENT_CACHING_ENABLED', true),
        'default_ttl' => env('CONTENT_CACHE_TTL', 3600), // 1 hour
        'profile_ttl' => env('PROFILE_CACHE_TTL', 7200), // 2 hours
        'suggestions_ttl' => env('SUGGESTIONS_CACHE_TTL', 1800), // 30 minutes
        'optimization_ttl' => env('OPTIMIZATION_CACHE_TTL', 3600), // 1 hour
    ],
    'ab_testing' => [
        'enabled' => env('CONTENT_AB_TESTING_ENABLED', false),
        'variants' => explode(',', env('CONTENT_AB_VARIANTS', 'openai,gemini')),
        'traffic_split' => env('CONTENT_AB_TRAFFIC_SPLIT', 0.5), // 50/50 split
        'min_sample_size' => env('CONTENT_AB_MIN_SAMPLE', 100),
    ],
    'monitoring' => [
        'enabled' => env('CONTENT_MONITORING_ENABLED', true),
        'log_generations' => env('LOG_GENERATIONS', true),
        'log_optimizations' => env('LOG_OPTIMIZATIONS', true),
        'log_errors' => env('LOG_CONTENT_ERRORS', true),
        'alert_thresholds' => [
            'error_rate' => env('CONTENT_ERROR_RATE_THRESHOLD', 0.1), // 10%
            'response_time' => env('CONTENT_RESPONSE_TIME_THRESHOLD', 5), // 5 seconds
            'safety_violations' => env('CONTENT_SAFETY_VIOLATION_THRESHOLD', 0.05), // 5%
        ],
    ],
];
