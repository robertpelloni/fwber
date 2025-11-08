<?php

return [
    // Global master switch
    'enabled' => env('FEATURE_FLAGS_ENABLED', true),

    // Individual flags with sane defaults; override via env: FLAG_<NAME>
    'flags' => [
        'onboarding_v1' => (bool) env('FLAG_ONBOARDING_V1', true),
        'matching_feed_v1' => (bool) env('FLAG_MATCHING_FEED_V1', true),
        'messaging_ws' => (bool) env('FLAG_MESSAGING_WS', false),
        'moderation_v2' => (bool) env('FLAG_MODERATION_V2', true),
        'reviewer_console' => (bool) env('FLAG_REVIEWER_CONSOLE', false),
        'analytics_v0' => (bool) env('FLAG_ANALYTICS_V0', true),
    ],
];
