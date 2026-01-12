<?php

return [
    // Core features - enabled by default
    'groups' => env('FEATURE_GROUPS', true),
    'photos' => env('FEATURE_PHOTOS', true),
    'proximity_artifacts' => env('FEATURE_PROXIMITY_ARTIFACTS', true),

    // Phase 4B features - UI complete, enabled by default (Jan 2026)
    'chatrooms' => env('FEATURE_CHATROOMS', true),
    'proximity_chatrooms' => env('FEATURE_PROXIMITY_CHATROOMS', true),
    'recommendations' => env('FEATURE_RECOMMENDATIONS', true),
    'ai_wingman' => env('FEATURE_AI_WINGMAN', true),
    'content_generation' => env('FEATURE_CONTENT_GENERATION', true),
    'achievements' => env('FEATURE_ACHIEVEMENTS', true),
    'bulletin_boards' => env('FEATURE_BULLETIN_BOARDS', true),
    'profile_views' => env('FEATURE_PROFILE_VIEWS', true),
    'share_unlock' => env('FEATURE_SHARE_UNLOCK', true),
    'viral_content' => env('FEATURE_VIRAL_CONTENT', true),
    'bounties' => env('FEATURE_BOUNTIES', true),

    // Infrastructure features - enable as needed
    'websocket' => env('FEATURE_WEBSOCKET', true),
    'rate_limits' => env('FEATURE_RATE_LIMITS', true),
    'analytics' => env('FEATURE_ANALYTICS', true),

    // Advanced features - enabled for testing
    'face_reveal' => env('FEATURE_FACE_REVEAL', true),
    'local_media_vault' => env('FEATURE_LOCAL_MEDIA_VAULT', true),
    'moderation' => env('FEATURE_MODERATION', true),
    'media_analysis' => env('FEATURE_MEDIA_ANALYSIS', true),
    'video_chat' => env('FEATURE_VIDEO_CHAT', true),
];
