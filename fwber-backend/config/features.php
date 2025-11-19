<?php

return [
    // Core/social features toggles
    'groups' => env('FEATURE_GROUPS', true),
    'photos' => env('FEATURE_PHOTOS', true),
    'proximity_artifacts' => env('FEATURE_PROXIMITY_ARTIFACTS', true),
    'chatrooms' => env('FEATURE_CHATROOMS', true),
    'proximity_chatrooms' => env('FEATURE_PROXIMITY_CHATROOMS', false),

    // Moderation and safety (Phase 2)
    'moderation' => env('FEATURE_MODERATION', false),

    // Advanced/optional features
    'recommendations' => env('FEATURE_RECOMMENDATIONS', false),
    'websocket' => env('FEATURE_WEBSOCKET', false),
    'content_generation' => env('FEATURE_CONTENT_GENERATION', false),
    'rate_limits' => env('FEATURE_RATE_LIMITS', false),
    'analytics' => env('FEATURE_ANALYTICS', false),
];
