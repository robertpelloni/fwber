<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WebSocketService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WebSocketController extends Controller
{
    private WebSocketService $webSocketService;

    public function __construct(WebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    /**
     * Handle WebSocket connection
     */
    public function connect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'connection_data' => 'array',
                'connection_data.user_agent' => 'string',
                'connection_data.ip_address' => 'string',
                'connection_data.device_type' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $connectionData = $request->get('connection_data', []);
            $result = $this->webSocketService->handleConnection($user->id, $connectionData);

            if ($result['success']) {
                return response()->json($result['data']);
            } else {
                return response()->json(['error' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket connection failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Connection failed'], 500);
        }
    }

    /**
     * Handle WebSocket disconnection
     */
    public function disconnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'connection_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $connectionId = $request->get('connection_id');
            $result = $this->webSocketService->handleDisconnection($connectionId);

            if ($result['success']) {
                return response()->json($result['data']);
            } else {
                return response()->json(['error' => $result['error']], 400);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket disconnection failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Disconnection failed'], 500);
        }
    }

    /**
     * Send message to user
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|string',
                'message' => 'required|array',
                'message.type' => 'required|string',
                'message.content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $recipientId = $request->get('recipient_id');
            $message = $request->get('message');

            $success = $this->webSocketService->handleChatMessage($user->id, $recipientId, $message);

            if ($success) {
                return response()->json(['message' => 'Message sent successfully']);
            } else {
                return response()->json(['error' => 'Failed to send message'], 500);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket message sending failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|string',
                'is_typing' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $recipientId = $request->get('recipient_id');
            $isTyping = $request->get('is_typing');

            $success = $this->webSocketService->handleTypingIndicator($user->id, $recipientId, $isTyping);

            if ($success) {
                return response()->json(['message' => 'Typing indicator sent']);
            } else {
                return response()->json(['error' => 'Failed to send typing indicator'], 500);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket typing indicator failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to send typing indicator'], 500);
        }
    }

    /**
     * Update presence status
     */
    public function updatePresence(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:online,away,busy,offline',
                'metadata' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $status = $request->get('status');
            $metadata = $request->get('metadata', []);

            $success = $this->webSocketService->sendPresenceUpdate($user->id, $status, $metadata);

            if ($success) {
                return response()->json(['message' => 'Presence updated successfully']);
            } else {
                return response()->json(['error' => 'Failed to update presence'], 500);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket presence update failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to update presence'], 500);
        }
    }

    /**
     * Send notification
     */
    public function sendNotification(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'recipient_id' => 'required|string',
                'notification' => 'required|array',
                'notification.title' => 'required|string',
                'notification.body' => 'required|string',
                'notification.type' => 'string',
                'notification.data' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $recipientId = $request->get('recipient_id');
            $notification = $request->get('notification');

            $success = $this->webSocketService->sendNotification($recipientId, $notification);

            if ($success) {
                return response()->json(['message' => 'Notification sent successfully']);
            } else {
                return response()->json(['error' => 'Failed to send notification'], 500);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket notification failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to send notification'], 500);
        }
    }

    /**
     * Get online users
     */
    public function getOnlineUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $onlineUsers = $this->webSocketService->getOnlineUsers();

            return response()->json([
                'online_users' => $onlineUsers,
                'total' => count($onlineUsers),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get online users', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to get online users'], 500);
        }
    }

    /**
     * Get user connections
     */
    public function getUserConnections(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $connections = $this->webSocketService->getUserConnections($user->id);

            return response()->json([
                'connections' => $connections,
                'total' => count($connections),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user connections', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to get user connections'], 500);
        }
    }

    /**
     * Broadcast message to multiple users
     */
    public function broadcast(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !$user->is_admin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'string',
                'message' => 'required|array',
                'message.type' => 'required|string',
                'message.content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $userIds = $request->get('user_ids');
            $message = $request->get('message');

            $success = $this->webSocketService->broadcastToUsers($userIds, $message);

            if ($success) {
                return response()->json([
                    'message' => 'Broadcast sent successfully',
                    'recipients' => count($userIds)
                ]);
            } else {
                return response()->json(['error' => 'Failed to broadcast message'], 500);
            }

        } catch (\Exception $e) {
            Log::error('WebSocket broadcast failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to broadcast message'], 500);
        }
    }

    /**
     * Get WebSocket status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $connections = $this->webSocketService->getUserConnections($user->id);
            $onlineUsers = $this->webSocketService->getOnlineUsers();

            return response()->json([
                'websocket_enabled' => config('websocket.enabled', true),
                'user_connections' => count($connections),
                'total_online_users' => count($onlineUsers),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get WebSocket status', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to get WebSocket status'], 500);
        }
    }
}
