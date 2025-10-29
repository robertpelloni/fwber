<?php

return [
    'enabled' => env('CONTENT_OPTIMIZATION_ENABLED', true),
    'providers' => explode(',', env('CONTENT_OPTIMIZATION_PROVIDERS', 'openai,gemini')),
    'optimization_types' => [
        'engagement' => [
            'weight' => env('ENGAGEMENT_OPTIMIZATION_WEIGHT', 0.4),
            'enabled' => env('ENGAGEMENT_OPTIMIZATION_ENABLED', true),
            'target_score' => env('ENGAGEMENT_TARGET_SCORE', 0.8),
        ],
        'clarity' => [
            'weight' => env('CLARITY_OPTIMIZATION_WEIGHT', 0.3),
            'enabled' => env('CLARITY_OPTIMIZATION_ENABLED', true),
            'target_score' => env('CLARITY_TARGET_SCORE', 0.8),
        ],
        'safety' => [
            'weight' => env('SAFETY_OPTIMIZATION_WEIGHT', 0.2),
            'enabled' => env('SAFETY_OPTIMIZATION_ENABLED', true),
            'target_score' => env('SAFETY_TARGET_SCORE', 0.9),
        ],
        'relevance' => [
            'weight' => env('RELEVANCE_OPTIMIZATION_WEIGHT', 0.1),
            'enabled' => env('RELEVANCE_OPTIMIZATION_ENABLED', true),
            'target_score' => env('RELEVANCE_TARGET_SCORE', 0.7),
        ],
    ],
    'cache_ttl' => env('OPTIMIZATION_CACHE_TTL', 3600), // 1 hour
    'max_iterations' => env('MAX_OPTIMIZATION_ITERATIONS', 3),
    'min_improvement_threshold' => env('MIN_OPTIMIZATION_IMPROVEMENT', 0.1),
    'quality_metrics' => [
        'readability' => [
            'enabled' => env('READABILITY_ANALYSIS_ENABLED', true),
            'target_score' => env('READABILITY_TARGET_SCORE', 0.7),
            'algorithm' => env('READABILITY_ALGORITHM', 'flesch'), // flesch, smog, ari
        ],
        'engagement' => [
            'enabled' => env('ENGAGEMENT_ANALYSIS_ENABLED', true),
            'indicators' => [
                'question_marks' => env('QUESTION_MARKS_WEIGHT', 0.1),
                'exclamation_marks' => env('EXCLAMATION_MARKS_WEIGHT', 0.05),
                'emojis' => env('EMOJIS_WEIGHT', 0.05),
                'call_to_action' => env('CTA_WEIGHT', 0.1),
            ],
        ],
        'clarity' => [
            'enabled' => env('CLARITY_ANALYSIS_ENABLED', true),
            'sentence_length_target' => env('SENTENCE_LENGTH_TARGET', 15), // words
            'vocabulary_complexity_threshold' => env('VOCAB_COMPLEXITY_THRESHOLD', 0.3),
        ],
        'safety' => [
            'enabled' => env('SAFETY_ANALYSIS_ENABLED', true),
            'moderation_required' => env('SAFETY_MODERATION_REQUIRED', true),
            'threshold' => env('SAFETY_THRESHOLD', 0.8),
        ],
    ],
    'rate_limits' => [
        'optimization_per_hour' => env('OPTIMIZATION_RATE_LIMIT', 30),
        'optimization_per_day' => env('OPTIMIZATION_DAILY_LIMIT', 200),
        'bulk_optimization' => env('BULK_OPTIMIZATION_LIMIT', 10),
    ],
    'feedback' => [
        'enabled' => env('OPTIMIZATION_FEEDBACK_ENABLED', true),
        'track_improvements' => env('TRACK_OPTIMIZATION_IMPROVEMENTS', true),
        'user_satisfaction_threshold' => env('USER_SATISFACTION_THRESHOLD', 0.7),
    ],
    'analytics' => [
        'enabled' => env('OPTIMIZATION_ANALYTICS_ENABLED', true),
        'track_performance' => env('TRACK_OPTIMIZATION_PERFORMANCE', true),
        'track_improvements' => env('TRACK_OPTIMIZATION_IMPROVEMENTS', true),
        'retention_days' => env('OPTIMIZATION_ANALYTICS_RETENTION', 90),
    ],
    'ab_testing' => [
        'enabled' => env('OPTIMIZATION_AB_TESTING_ENABLED', false),
        'variants' => explode(',', env('OPTIMIZATION_AB_VARIANTS', 'openai,gemini')),
        'traffic_split' => env('OPTIMIZATION_AB_TRAFFIC_SPLIT', 0.5),
        'min_sample_size' => env('OPTIMIZATION_AB_MIN_SAMPLE', 50),
    ],
    'monitoring' => [
        'enabled' => env('OPTIMIZATION_MONITORING_ENABLED', true),
        'log_optimizations' => env('LOG_OPTIMIZATIONS', true),
        'log_errors' => env('LOG_OPTIMIZATION_ERRORS', true),
        'alert_thresholds' => [
            'error_rate' => env('OPTIMIZATION_ERROR_RATE_THRESHOLD', 0.1),
            'response_time' => env('OPTIMIZATION_RESPONSE_TIME_THRESHOLD', 3),
            'improvement_rate' => env('OPTIMIZATION_IMPROVEMENT_RATE_THRESHOLD', 0.2),
        ],
    ],
    'content_types' => [
        'profile' => [
            'optimization_enabled' => env('PROFILE_OPTIMIZATION_ENABLED', true),
            'max_length' => env('PROFILE_MAX_LENGTH', 500),
            'min_length' => env('PROFILE_MIN_LENGTH', 50),
            'required_optimizations' => ['clarity', 'engagement'],
        ],
        'post' => [
            'optimization_enabled' => env('POST_OPTIMIZATION_ENABLED', true),
            'max_length' => env('POST_MAX_LENGTH', 1000),
            'min_length' => env('POST_MIN_LENGTH', 20),
            'required_optimizations' => ['engagement', 'relevance'],
        ],
        'message' => [
            'optimization_enabled' => env('MESSAGE_OPTIMIZATION_ENABLED', true),
            'max_length' => env('MESSAGE_MAX_LENGTH', 2000),
            'min_length' => env('MESSAGE_MIN_LENGTH', 10),
            'required_optimizations' => ['clarity', 'safety'],
        ],
    ],
    'advanced_features' => [
        'multi_language' => env('MULTI_LANGUAGE_OPTIMIZATION', false),
        'context_awareness' => env('CONTEXT_AWARE_OPTIMIZATION', true),
        'personalization' => env('PERSONALIZED_OPTIMIZATION', true),
        'real_time' => env('REAL_TIME_OPTIMIZATION', false),
    ],
];
