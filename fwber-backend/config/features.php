<?php

return [
    // Core/social features toggles
    'groups' => env('FEATURE_GROUPS', true),
    'photos' => env('FEATURE_PHOTOS', true),
    'proximity_artifacts' => env('FEATURE_PROXIMITY_ARTIFACTS', true),
    'chatrooms' => env('FEATURE_CHATROOMS', true),
    'proximity_chatrooms' => env('FEATURE_PROXIMITY_CHATROOMS', false),
    'face_reveal' => env('FEATURE_FACE_REVEAL', false),
    'local_media_vault' => env('FEATURE_LOCAL_MEDIA_VAULT', false),

    // Moderation and safety (Phase 2)
    'moderation' => env('FEATURE_MODERATION', false),

    // Advanced/optional features
    'recommendations' => env('FEATURE_RECOMMENDATIONS', false),
    'websocket' => env('FEATURE_WEBSOCKET', false),
    'content_generation' => env('FEATURE_CONTENT_GENERATION', false),
    'rate_limits' => env('FEATURE_RATE_LIMITS', false),
    'analytics' => env('FEATURE_ANALYTICS', false),
];
