<?php

return [
    // Whether emitting telemetry is enabled
    'enabled' => env('TELEMETRY_ENABLED', true),
    'store_events' => env('TELEMETRY_STORE_EVENTS', true),

    // Map of event name => validation rules
    'schemas' => [
        'user.signup' => [
            'user_id' => 'required|integer',
            'method' => 'required|string',
        ],
        'user.profile.completed' => [
            'user_id' => 'required|integer',
            'completion_rate' => 'required|numeric|min:0|max:1',
        ],
        'feed.viewed' => [
            'user_id' => 'required|integer',
            'count' => 'required|integer|min:0',
        ],
        'message.sent' => [
            'from_user_id' => 'required|integer',
            'to_user_id' => 'required|integer',
            'message_id' => 'nullable|string',
        ],
        'message.received' => [
            'from_user_id' => 'required|integer',
            'to_user_id' => 'required|integer',
            'message_id' => 'nullable|string',
        ],
        'moderation.flagged' => [
            'user_id' => 'required|integer',
            'category' => 'required|string',
            'confidence' => 'required|numeric|min:0|max:1',
        ],
        'moderation.actioned' => [
            'user_id' => 'required|integer',
            'action' => 'required|string|in:reject,review,flag,approve',
        ],
        'face_blur_applied' => [
            'user_id' => 'required|integer',
            'photo_filename' => 'required|string',
            'original_filename' => 'required|string',
            'faces_detected' => 'required|integer|min:0',
            'processing_ms' => 'nullable|integer|min:0',
            'client_backend' => 'required|string|in:client,server',
            'warning' => 'nullable|string',
            'preview_id' => 'nullable|string',
        ],
        'face_blur_skipped_reason' => [
            'user_id' => 'required|integer',
            'photo_filename' => 'required|string',
            'original_filename' => 'required|string',
            'reason' => 'required|string',
            'faces_detected' => 'nullable|integer|min:0',
            'warning' => 'nullable|string',
            'preview_id' => 'nullable|string',
        ],
        'face_blur_preview_ready' => [
            'user_id' => 'required|integer',
            'preview_id' => 'required|string',
            'file_name' => 'required|string',
            'faces_detected' => 'nullable|integer|min:0',
            'blur_applied' => 'required|boolean',
            'processing_ms' => 'nullable|integer|min:0',
            'backend' => 'required|string|in:client,server',
            'warning' => 'nullable|string',
            'skipped_reason' => 'nullable|string',
        ],
        'face_blur_preview_toggled' => [
            'user_id' => 'required|integer',
            'preview_id' => 'required|string',
            'view' => 'required|string|in:original,processed',
            'faces_detected' => 'nullable|integer|min:0',
            'blur_applied' => 'nullable|boolean',
            'warning' => 'nullable|string',
        ],
        'face_blur_preview_discarded' => [
            'user_id' => 'required|integer',
            'preview_id' => 'required|string',
            'faces_detected' => 'nullable|integer|min:0',
            'blur_applied' => 'nullable|boolean',
            'discard_reason' => 'required|string|in:user_removed,validation_failed',
            'warning' => 'nullable|string',
        ],
    ],
];
