<?php

return [
    // Core features - verified via tests, enabled by default
    'groups' => env('FEATURE_GROUPS', true),
    'photos' => env('FEATURE_PHOTOS', true),
    'proximity_artifacts' => env('FEATURE_PROXIMITY_ARTIFACTS', true),
    'chatrooms' => env('FEATURE_CHATROOMS', true),
    'proximity_chatrooms' => env('FEATURE_PROXIMITY_CHATROOMS', true),
    'recommendations' => env('FEATURE_RECOMMENDATIONS', true),
    'achievements' => env('FEATURE_ACHIEVEMENTS', true),
    'profile_views' => env('FEATURE_PROFILE_VIEWS', true),
    'viral_content' => env('FEATURE_VIRAL_CONTENT', true),
    'bounties' => env('FEATURE_BOUNTIES', true),

    // Infrastructure - enable as needed
    'websocket' => env('FEATURE_WEBSOCKET', true),
    'rate_limits' => env('FEATURE_RATE_LIMITS', true),
    'analytics' => env('FEATURE_ANALYTICS', true),
    'moderation' => env('FEATURE_MODERATION', true),

    // Beta features - require API keys or external services
    'ai_wingman' => env('FEATURE_AI_WINGMAN', false),          // Requires LLM API key
    'content_generation' => env('FEATURE_CONTENT_GENERATION', false), // Requires LLM API key
    'bulletin_boards' => env('FEATURE_BULLETIN_BOARDS', true),
    'share_unlock' => env('FEATURE_SHARE_UNLOCK', true),

    // Experimental - disabled until verified in production
    'face_reveal' => env('FEATURE_FACE_REVEAL', false),        // Needs face-api.js browser testing
    'local_media_vault' => env('FEATURE_LOCAL_MEDIA_VAULT', false), // Needs storage verification
    'media_analysis' => env('FEATURE_MEDIA_ANALYSIS', false),  // Needs ML model deployment
    'video_chat' => env('FEATURE_VIDEO_CHAT', false),          // Needs WebRTC E2E testing
];

