<?php

return [
    'groups' => env('FEATURE_GROUPS', true),
    'photos' => env('FEATURE_PHOTOS', true),
    'proximity_artifacts' => env('FEATURE_PROXIMITY_ARTIFACTS', true),
    'chatrooms' => env('FEATURE_CHATROOMS', false),
    'proximity_chatrooms' => env('FEATURE_PROXIMITY_CHATROOMS', false),
    'recommendations' => env('FEATURE_RECOMMENDATIONS', false),
    'websocket' => env('FEATURE_WEBSOCKET', false),
    'content_generation' => env('FEATURE_CONTENT_GENERATION', false),
    'rate_limits' => env('FEATURE_RATE_LIMITS', false),
    'analytics' => env('FEATURE_ANALYTICS', false),
    'face_reveal' => env('FEATURE_FACE_REVEAL', false),
    'local_media_vault' => env('FEATURE_LOCAL_MEDIA_VAULT', false),
    'moderation' => env('FEATURE_MODERATION', false),
    'media_analysis' => env('FEATURE_MEDIA_ANALYSIS', false),
    'ai_wingman' => env('FEATURE_AI_WINGMAN', false),
];
