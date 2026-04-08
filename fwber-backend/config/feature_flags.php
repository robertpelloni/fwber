<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags Master Switch
    |--------------------------------------------------------------------------
    |
    | When false, all feature flag checks return false regardless of individual
    | flag settings.  Individual flags are booleans keyed by feature name.
    |
    */

    'enabled' => (bool) env('FEATURE_FLAGS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Individual Feature Flags
    |--------------------------------------------------------------------------
    |
    | Each key maps to a feature that can be toggled at runtime.  Flags that
    | are set to true will be available to all users; those set to false will
    | be hidden from the frontend and gated in the backend.
    |
    */

    'flags' => [
        // Core social features
        'groups'                => (bool) env('FEATURE_GROUPS', true),
        'photos'                => (bool) env('FEATURE_PHOTOS', true),
        'face_reveal'           => (bool) env('FEATURE_FACE_REVEAL', true),
        'local_media_vault'     => (bool) env('FEATURE_LOCAL_MEDIA_VAULT', true),

        // Proximity & location
        'proximity_artifacts'   => (bool) env('FEATURE_PROXIMITY_ARTIFACTS', true),
        'chatrooms'             => (bool) env('FEATURE_CHATROOMS', true),
        'proximity_chatrooms'   => (bool) env('FEATURE_PROXIMITY_CHATROOMS', true),

        // Safety & moderation
        'moderation'            => (bool) env('FEATURE_MODERATION', true),

        // AI & content
        'recommendations'       => (bool) env('FEATURE_RECOMMENDATIONS', true),
        'content_generation'    => (bool) env('FEATURE_CONTENT_GENERATION', true),
        'ai_wingman'            => (bool) env('FEATURE_AI_WINGMAN', true),
        'media_analysis'        => (bool) env('FEATURE_MEDIA_ANALYSIS', true),
        'video_chat'            => (bool) env('FEATURE_VIDEO_CHAT', true),
    ],

];
