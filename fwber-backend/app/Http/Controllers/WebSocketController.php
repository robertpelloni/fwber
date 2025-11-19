<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WebSocketService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WebSocketController extends Controller
{
    private WebSocketService $webSocketService;

    public function __construct(WebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    /**
     * Handle WebSocket connection
     * 
     * @OA\Post(
     *     path="/websocket/connect",
     *     tags={"WebSocket"},
     *     summary="Connect to WebSocket",
     *     description="Establish a WebSocket connection for real-time communication",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="connection_data", type="object",
     *                 @OA\Property(property="user_agent", type="string", example="Mozilla/5.0..."),
     *                 @OA\Property(property="ip_address", type="string", example="192.168.1.1"),
     *                 @OA\Property(property="device_type", type="string", example="mobile")
     *             )
     *         )
     *     ),
    *     @OA\Response(response=200, description="Connection established",
    *         @OA\JsonContent(ref="#/components/schemas/WebSocketConnectionEstablished")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/disconnect",
     *     tags={"WebSocket"},
     *     summary="Disconnect from WebSocket",
     *     description="Close an active WebSocket connection",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"connection_id"},
     *             @OA\Property(property="connection_id", type="string", example="conn_abc123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Disconnected successfully"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/message",
     *     tags={"WebSocket"},
     *     summary="Send WebSocket message",
     *     description="Send a real-time message to another user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_id", "message"},
     *             @OA\Property(property="recipient_id", type="string", example="user_123"),
     *             @OA\Property(property="message", type="object",
     *                 required={"type", "content"},
     *                 @OA\Property(property="type", type="string", example="text"),
     *                 @OA\Property(property="content", type="string", example="Hello there!")
     *             )
     *         )
     *     ),
    *     @OA\Response(response=200, description="Message sent successfully",
    *         @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/typing",
     *     tags={"WebSocket"},
     *     summary="Send typing indicator",
     *     description="Notify another user that you are typing",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_id", "is_typing"},
     *             @OA\Property(property="recipient_id", type="string", example="user_123"),
     *             @OA\Property(property="is_typing", type="boolean", example=true)
     *         )
     *     ),
    *     @OA\Response(response=200, description="Typing indicator sent",
    *         @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/presence",
     *     tags={"WebSocket"},
     *     summary="Update presence status",
     *     description="Update your online/away/busy/offline status",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"online", "away", "busy", "offline"}, example="online"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
    *     @OA\Response(response=200, description="Presence updated successfully",
    *         @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/notification",
     *     tags={"WebSocket"},
     *     summary="Send real-time notification",
     *     description="Send a push notification to another user via WebSocket",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_id", "notification"},
     *             @OA\Property(property="recipient_id", type="string", example="user_123"),
     *             @OA\Property(property="notification", type="object",
     *                 required={"title", "body"},
     *                 @OA\Property(property="title", type="string", example="New Match!"),
     *                 @OA\Property(property="body", type="string", example="You have a new match nearby"),
     *                 @OA\Property(property="type", type="string", example="match"),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     ),
    *     @OA\Response(response=200, description="Notification sent successfully",
    *         @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Get(
     *     path="/websocket/online-users",
     *     tags={"WebSocket"},
     *     summary="Get online users",
     *     description="Retrieve a list of currently online users",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of online users",
     *         @OA\JsonContent(
     *             @OA\Property(property="online_users", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
     * 
     * @OA\Get(
     *     path="/websocket/connections",
     *     tags={"WebSocket"},
     *     summary="Get user connections",
     *     description="Retrieve active WebSocket connections for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User connections",
     *         @OA\JsonContent(
     *             @OA\Property(property="connections", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
     * 
     * @OA\Post(
     *     path="/websocket/broadcast",
     *     tags={"WebSocket"},
     *     summary="Broadcast message",
     *     description="Send a message to multiple users (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_ids", "message"},
     *             @OA\Property(property="user_ids", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="object",
     *                 required={"type", "content"},
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="content", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Broadcast sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="recipients", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
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
     * 
     * @OA\Get(
     *     path="/websocket/status",
     *     tags={"WebSocket"},
     *     summary="Get WebSocket status",
     *     description="Check the status of the WebSocket service",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="WebSocket status",
     *         @OA\JsonContent(
     *             @OA\Property(property="websocket_enabled", type="boolean"),
     *             @OA\Property(property="user_connections", type="integer"),
     *             @OA\Property(property="total_online_users", type="integer"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
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
