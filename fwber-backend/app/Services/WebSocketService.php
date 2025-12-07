<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Services\MercurePublisher;

class WebSocketService
{
    private MercurePublisher $mercurePublisher;
    private array $config;

    public function __construct(MercurePublisher $mercurePublisher)
    {
        $this->mercurePublisher = $mercurePublisher;
        $this->config = config('websocket', [
            'enabled' => true,
            'redis_channel' => 'websocket',
            'presence_channel' => 'presence',
            'notification_channel' => 'notifications',
            'chat_channel' => 'chat',
            'heartbeat_interval' => 30, // seconds
            'connection_timeout' => 300, // 5 minutes
            'max_connections_per_user' => 3,
        ]);
    }

    /**
     * Get Mercure JWT token for user
     */
    public function getMercureToken(string $userId): string
    {
        $topics = [
            "https://fwber.me/user/{$userId}",
            "https://fwber.me/presence"
        ];

        return $this->mercurePublisher->generateSubscriberJWT($topics, (int) $userId);
    }

    /**
     * Handle WebSocket connection
     */
    public function handleConnection(string $userId, array $connectionData): array
    {
        try {
            // Validate user
            $user = User::find($userId);
            if (!$user) {
                return $this->errorResponse('User not found');
            }

            // Check connection limits
            if (!$this->checkConnectionLimits($userId)) {
                return $this->errorResponse('Connection limit exceeded');
            }

            // Register connection
            $connectionId = $this->registerConnection($userId, $connectionData);

            // Subscribe to user-specific channels
            $this->subscribeToUserChannels($userId, $connectionId);

            // Send welcome message
            $this->sendToConnection($connectionId, [
                'type' => 'connection_established',
                'connection_id' => $connectionId,
                'user_id' => $userId,
                'timestamp' => now()->toISOString(),
            ]);

            return $this->successResponse([
                'connection_id' => $connectionId,
                'channels' => $this->getUserChannels($userId),
                'heartbeat_interval' => $this->config['heartbeat_interval'],
            ]);

        } catch (\Exception $e) {
            Log::error('WebSocket connection failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Connection failed');
        }
    }

    /**
     * Handle WebSocket disconnection
     */
    public function handleDisconnection(string $connectionId): array
    {
        try {
            // Get connection info
            $connectionInfo = $this->getConnectionInfo($connectionId);
            if (!$connectionInfo) {
                return $this->errorResponse('Connection not found');
            }

            // Unsubscribe from channels
            $this->unsubscribeFromChannels($connectionId);

            // Remove connection
            $this->removeConnection($connectionId);

            // Notify other connections if needed
            if ($connectionInfo['user_id']) {
                $this->notifyUserPresenceChange($connectionInfo['user_id'], 'offline');
            }

            return $this->successResponse(['connection_id' => $connectionId]);

        } catch (\Exception $e) {
            Log::error('WebSocket disconnection failed', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Disconnection failed');
        }
    }

    /**
     * Send message to specific connection
     */
    public function sendToConnection(string $connectionId, array $message): bool
    {
        try {
            $message['timestamp'] = now()->toISOString();
            $message['connection_id'] = $connectionId;

            // Publish to Redis for WebSocket server to pick up
            Redis::publish($this->config['redis_channel'], json_encode([
                'action' => 'send_to_connection',
                'connection_id' => $connectionId,
                'message' => $message
            ]));

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send message to connection', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send message to user (all their connections)
     */
    public function sendToUser(string $userId, array $message): bool
    {
        try {
            $message['timestamp'] = now()->toISOString();
            $message['user_id'] = $userId;

            // Publish to user channel via Redis (for legacy WebSocket)
            Redis::publish($this->config['redis_channel'], json_encode([
                'action' => 'send_to_user',
                'user_id' => $userId,
                'message' => $message
            ]));

            // Publish to Mercure
            $this->mercurePublisher->publish(
                "https://fwber.me/user/{$userId}",
                $message,
                true // Private update
            );

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send message to user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Broadcast message to multiple users
     */
    public function broadcastToUsers(array $userIds, array $message): bool
    {
        try {
            $message['timestamp'] = now()->toISOString();
            $message['broadcast'] = true;

            foreach ($userIds as $userId) {
                // Redis
                Redis::publish($this->config['redis_channel'], json_encode([
                    'action' => 'send_to_user',
                    'user_id' => $userId,
                    'message' => $message
                ]));

                // Mercure
                $this->mercurePublisher->publish(
                    "https://fwber.me/user/{$userId}",
                    $message,
                    true
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to broadcast message', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send presence update
     */
    public function sendPresenceUpdate(string $userId, string $status, array $metadata = []): bool
    {
        try {
            $message = [
                'type' => 'presence_update',
                'user_id' => $userId,
                'status' => $status,
                'metadata' => $metadata,
                'timestamp' => now()->toISOString(),
            ];

            // Update presence in Redis
            Redis::hset('user_presence', $userId, json_encode([
                'status' => $status,
                'metadata' => $metadata,
                'last_seen' => now()->toISOString(),
            ]));

            // Notify presence channel via Redis
            Redis::publish($this->config['presence_channel'], json_encode($message));

            // Notify via Mercure (public topic for presence)
            $this->mercurePublisher->publish(
                "https://fwber.me/presence",
                $message,
                false // Public update
            );

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send presence update', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send notification
     */
    public function sendNotification(string $userId, array $notification): bool
    {
        try {
            $message = [
                'type' => 'notification',
                'user_id' => $userId,
                'notification' => $notification,
                'timestamp' => now()->toISOString(),
            ];

            // Store notification in database
            $this->storeNotification($userId, $notification);

            // Send via WebSocket
            $this->sendToUser($userId, $message);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Handle chat message
     */
    public function handleChatMessage(string $userId, string $recipientId, array $message): bool
    {
        try {
            $chatMessage = [
                'type' => 'chat_message',
                'from_user_id' => $userId,
                'to_user_id' => $recipientId,
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ];

            // Send to recipient
            $this->sendToUser($recipientId, $chatMessage);

            // Send confirmation to sender
            $this->sendToUser($userId, [
                'type' => 'message_sent',
                'message_id' => $message['id'] ?? uniqid(),
                'timestamp' => now()->toISOString(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle chat message', [
                'user_id' => $userId,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Handle typing indicator
     */
    public function handleTypingIndicator(string $userId, string $recipientId, bool $isTyping): bool
    {
        try {
            $message = [
                'type' => 'typing_indicator',
                'from_user_id' => $userId,
                'to_user_id' => $recipientId,
                'is_typing' => $isTyping,
                'timestamp' => now()->toISOString(),
            ];

            $this->sendToUser($recipientId, $message);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to handle typing indicator', [
                'user_id' => $userId,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get online users
     */
    public function getOnlineUsers(): array
    {
        try {
            $presenceData = Redis::hgetall('user_presence');
            $onlineUsers = [];

            foreach ($presenceData as $userId => $data) {
                $userData = json_decode($data, true);
                if ($userData && $userData['status'] === 'online') {
                    $onlineUsers[] = [
                        'user_id' => $userId,
                        'status' => $userData['status'],
                        'last_seen' => $userData['last_seen'],
                        'metadata' => $userData['metadata'] ?? [],
                    ];
                }
            }

            return $onlineUsers;

        } catch (\Exception $e) {
            Log::error('Failed to get online users', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get user connections
     */
    public function getUserConnections(string $userId): array
    {
        try {
            $connections = Redis::hgetall("user_connections:{$userId}");
            $connectionList = [];

            foreach ($connections as $connectionId => $data) {
                $connectionData = json_decode($data, true);
                if ($connectionData) {
                    $connectionList[] = [
                        'connection_id' => $connectionId,
                        'connected_at' => $connectionData['connected_at'],
                        'last_activity' => $connectionData['last_activity'],
                        'metadata' => $connectionData['metadata'] ?? [],
                    ];
                }
            }

            return $connectionList;

        } catch (\Exception $e) {
            Log::error('Failed to get user connections', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Check connection limits
     */
    private function checkConnectionLimits(string $userId): bool
    {
        $connections = $this->getUserConnections($userId);
        return count($connections) < $this->config['max_connections_per_user'];
    }

    /**
     * Register connection
     */
    private function registerConnection(string $userId, array $connectionData): string
    {
        $connectionId = uniqid('ws_');
        
        Redis::hset("user_connections:{$userId}", $connectionId, json_encode([
            'connected_at' => now()->toISOString(),
            'last_activity' => now()->toISOString(),
            'metadata' => $connectionData,
        ]));

        Redis::hset('connection_users', $connectionId, $userId);

        return $connectionId;
    }

    /**
     * Remove connection
     */
    private function removeConnection(string $connectionId): void
    {
        $userId = Redis::hget('connection_users', $connectionId);
        
        if ($userId) {
            Redis::hdel("user_connections:{$userId}", $connectionId);
            Redis::hdel('connection_users', $connectionId);
        }
    }

    /**
     * Get connection info
     */
    private function getConnectionInfo(string $connectionId): ?array
    {
        $userId = Redis::hget('connection_users', $connectionId);
        
        if (!$userId) {
            return null;
        }

        $connectionData = Redis::hget("user_connections:{$userId}", $connectionId);
        
        if (!$connectionData) {
            return null;
        }

        return [
            'connection_id' => $connectionId,
            'user_id' => $userId,
            'data' => json_decode($connectionData, true),
        ];
    }

    /**
     * Subscribe to user channels
     */
    private function subscribeToUserChannels(string $userId, string $connectionId): void
    {
        $channels = [
            "user:{$userId}",
            "notifications:{$userId}",
            "presence:{$userId}",
        ];

        foreach ($channels as $channel) {
            Redis::sadd("connection_channels:{$connectionId}", $channel);
        }
    }

    /**
     * Unsubscribe from channels
     */
    private function unsubscribeFromChannels(string $connectionId): void
    {
        Redis::del("connection_channels:{$connectionId}");
    }

    /**
     * Get user channels
     */
    private function getUserChannels(string $userId): array
    {
        return [
            "user:{$userId}",
            "notifications:{$userId}",
            "presence:{$userId}",
        ];
    }

    /**
     * Notify user presence change
     */
    private function notifyUserPresenceChange(string $userId, string $status): void
    {
        $this->sendPresenceUpdate($userId, $status);
    }

    /**
     * Store notification
     */
    private function storeNotification(string $userId, array $notification): void
    {
        // This would store the notification in the database
        // For now, just log it
        Log::info('Notification stored', [
            'user_id' => $userId,
            'notification' => $notification
        ]);
    }

    /**
     * Success response
     */
    private function successResponse(array $data = []): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Error response
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}
