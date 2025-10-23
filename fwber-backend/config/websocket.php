<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the WebSocket service,
    | including connection settings, channels, and performance parameters.
    |
    */

    'enabled' => env('WEBSOCKET_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket connection parameters including timeouts,
    | heartbeat intervals, and connection limits.
    |
    */
    'connection' => [
        'timeout' => env('WEBSOCKET_TIMEOUT', 300), // 5 minutes
        'heartbeat_interval' => env('WEBSOCKET_HEARTBEAT_INTERVAL', 30), // 30 seconds
        'max_connections_per_user' => env('WEBSOCKET_MAX_CONNECTIONS_PER_USER', 3),
        'max_total_connections' => env('WEBSOCKET_MAX_TOTAL_CONNECTIONS', 1000),
        'connection_cleanup_interval' => env('WEBSOCKET_CLEANUP_INTERVAL', 60), // 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis channels and keys for WebSocket communication.
    |
    */
    'redis' => [
        'channel' => env('WEBSOCKET_REDIS_CHANNEL', 'websocket'),
        'presence_channel' => env('WEBSOCKET_PRESENCE_CHANNEL', 'presence'),
        'notification_channel' => env('WEBSOCKET_NOTIFICATION_CHANNEL', 'notifications'),
        'chat_channel' => env('WEBSOCKET_CHAT_CHANNEL', 'chat'),
        'connection_prefix' => env('WEBSOCKET_CONNECTION_PREFIX', 'ws_connection'),
        'user_prefix' => env('WEBSOCKET_USER_PREFIX', 'ws_user'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Types
    |--------------------------------------------------------------------------
    |
    | Define the types of messages that can be sent through WebSocket.
    |
    */
    'message_types' => [
        'connection_established' => 'Connection established successfully',
        'connection_closed' => 'Connection closed',
        'presence_update' => 'User presence status changed',
        'chat_message' => 'Chat message sent',
        'typing_indicator' => 'User is typing',
        'notification' => 'System notification',
        'message_sent' => 'Message delivery confirmation',
        'heartbeat' => 'Connection heartbeat',
        'error' => 'Error message',
        'broadcast' => 'Broadcast message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presence Settings
    |--------------------------------------------------------------------------
    |
    | Configure user presence tracking and status updates.
    |
    */
    'presence' => [
        'enabled' => env('WEBSOCKET_PRESENCE_ENABLED', true),
        'statuses' => ['online', 'away', 'busy', 'offline'],
        'default_status' => 'online',
        'away_timeout' => env('WEBSOCKET_AWAY_TIMEOUT', 300), // 5 minutes
        'offline_timeout' => env('WEBSOCKET_OFFLINE_TIMEOUT', 900), // 15 minutes
        'track_activity' => env('WEBSOCKET_TRACK_ACTIVITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure real-time notifications and delivery.
    |
    */
    'notifications' => [
        'enabled' => env('WEBSOCKET_NOTIFICATIONS_ENABLED', true),
        'types' => [
            'message' => 'New message received',
            'match' => 'New match found',
            'like' => 'Someone liked your profile',
            'bulletin' => 'New bulletin board message',
            'system' => 'System notification',
        ],
        'retry_attempts' => env('WEBSOCKET_NOTIFICATION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBSOCKET_NOTIFICATION_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Settings
    |--------------------------------------------------------------------------
    |
    | Configure real-time chat functionality.
    |
    */
    'chat' => [
        'enabled' => env('WEBSOCKET_CHAT_ENABLED', true),
        'typing_indicator_timeout' => env('WEBSOCKET_TYPING_TIMEOUT', 3000), // 3 seconds
        'message_history_limit' => env('WEBSOCKET_MESSAGE_HISTORY_LIMIT', 100),
        'rate_limit' => [
            'enabled' => env('WEBSOCKET_CHAT_RATE_LIMIT_ENABLED', true),
            'messages_per_minute' => env('WEBSOCKET_CHAT_RATE_LIMIT', 60),
            'burst_limit' => env('WEBSOCKET_CHAT_BURST_LIMIT', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket security and authentication.
    |
    */
    'security' => [
        'authentication_required' => env('WEBSOCKET_AUTH_REQUIRED', true),
        'token_validation' => env('WEBSOCKET_TOKEN_VALIDATION', true),
        'ip_whitelist' => env('WEBSOCKET_IP_WHITELIST', ''),
        'cors_origins' => env('WEBSOCKET_CORS_ORIGINS', '*'),
        'rate_limiting' => [
            'enabled' => env('WEBSOCKET_RATE_LIMITING_ENABLED', true),
            'requests_per_minute' => env('WEBSOCKET_RATE_LIMIT', 120),
            'burst_limit' => env('WEBSOCKET_BURST_LIMIT', 20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket performance and optimization.
    |
    */
    'performance' => [
        'message_batching' => env('WEBSOCKET_MESSAGE_BATCHING', true),
        'batch_size' => env('WEBSOCKET_BATCH_SIZE', 10),
        'batch_timeout' => env('WEBSOCKET_BATCH_TIMEOUT', 100), // milliseconds
        'compression' => env('WEBSOCKET_COMPRESSION', true),
        'compression_threshold' => env('WEBSOCKET_COMPRESSION_THRESHOLD', 1024), // bytes
        'connection_pooling' => env('WEBSOCKET_CONNECTION_POOLING', true),
        'pool_size' => env('WEBSOCKET_POOL_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket monitoring and metrics collection.
    |
    */
    'monitoring' => [
        'enabled' => env('WEBSOCKET_MONITORING_ENABLED', true),
        'metrics' => [
            'connection_count' => true,
            'message_count' => true,
            'error_count' => true,
            'response_time' => true,
            'throughput' => true,
        ],
        'alerts' => [
            'max_connections_threshold' => env('WEBSOCKET_MAX_CONNECTIONS_ALERT', 800),
            'error_rate_threshold' => env('WEBSOCKET_ERROR_RATE_THRESHOLD', 0.05), // 5%
            'response_time_threshold' => env('WEBSOCKET_RESPONSE_TIME_THRESHOLD', 1000), // 1 second
        ],
        'logging' => [
            'enabled' => env('WEBSOCKET_LOGGING_ENABLED', true),
            'level' => env('WEBSOCKET_LOG_LEVEL', 'info'),
            'log_connections' => env('WEBSOCKET_LOG_CONNECTIONS', true),
            'log_messages' => env('WEBSOCKET_LOG_MESSAGES', false),
            'log_errors' => env('WEBSOCKET_LOG_ERRORS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    |
    | Configure fallback behavior when WebSocket is unavailable.
    |
    */
    'fallback' => [
        'enabled' => env('WEBSOCKET_FALLBACK_ENABLED', true),
        'strategy' => env('WEBSOCKET_FALLBACK_STRATEGY', 'polling'), // polling, sse, none
        'polling_interval' => env('WEBSOCKET_FALLBACK_POLLING_INTERVAL', 5000), // 5 seconds
        'sse_endpoint' => env('WEBSOCKET_FALLBACK_SSE_ENDPOINT', '/api/events/stream'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Configure development-specific WebSocket settings.
    |
    */
    'development' => [
        'debug' => env('WEBSOCKET_DEBUG', false),
        'mock_connections' => env('WEBSOCKET_MOCK_CONNECTIONS', false),
        'test_mode' => env('WEBSOCKET_TEST_MODE', false),
        'simulate_latency' => env('WEBSOCKET_SIMULATE_LATENCY', false),
        'latency_range' => [50, 200], // milliseconds
    ],
];
