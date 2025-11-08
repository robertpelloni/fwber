<?php

return [
    // Whether emitting telemetry is enabled
    'enabled' => env('TELEMETRY_ENABLED', true),

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
    ],
];
