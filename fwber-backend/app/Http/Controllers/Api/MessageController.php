<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Message;
use App\Models\RelationshipTier;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/messages",
     *     tags={"Messages"},
     *     summary="Send a message",
     *     description="Send a text message or media (image/video/audio/file) to a matched user. Either content or media is required. Enforces relationship tier limits and blocks.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"receiver_id"},
     *                 @OA\Property(property="receiver_id", type="integer", description="ID of message recipient", example=42),
     *                 @OA\Property(property="content", type="string", maxLength=5000, description="Text message content", example="Hey, how are you?"),
     *                 @OA\Property(property="message_type", type="string", enum={"text", "image", "video", "audio", "file"}, example="text"),
     *                 @OA\Property(property="media", type="string", format="binary", description="Media file (image/video/audio/file)"),
     *                 @OA\Property(property="media_duration", type="integer", description="Duration in seconds for audio/video", example=30)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="sender_id", type="integer", example=1),
     *             @OA\Property(property="receiver_id", type="integer", example=42),
     *             @OA\Property(property="content", type="string", example="Hey, how are you?"),
     *             @OA\Property(property="message_type", type="string", example="text"),
     *             @OA\Property(property="media_url", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Messaging blocked or not allowed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Messaging blocked between users")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active match found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No active match found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     * 
     * Send a message to a matched user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'nullable|string|max:5000',
            'message_type' => 'sometimes|string|in:text,image,video,audio,file',
            'media' => 'nullable|file',
            // media_duration provided by clients for audio/video; we clamp to type-specific caps later
            'media_duration' => 'nullable|integer|min:1',
        ]);

        $senderId = Auth::id();
        $receiverId = $validated['receiver_id'];

        // Block enforcement (either direction blocks messaging)
        if (Block::isBlockedBetween($senderId, $receiverId)) {
            return response()->json(['error' => 'Messaging blocked between users'], 403);
        }

        // Find the match between these users
        $match = UserMatch::where(function ($query) use ($senderId, $receiverId) {
            $query->where('user1_id', $senderId)->where('user2_id', $receiverId)
                  ->orWhere('user1_id', $receiverId)->where('user2_id', $senderId);
        })->where('is_active', true)->first();

        if (!$match) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        // At least one of content or media is required
        if (!$request->hasFile('media') && (!isset($validated['content']) || trim((string)$validated['content']) === '')) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'content' => ['Either content or media is required']
                ]
            ], 422);
        }

        // Determine and validate media constraints
        $mediaUrl = null;
        $mediaType = null;
        $thumbnailUrl = null;
        $resolvedType = $validated['message_type'] ?? 'text';
        $duration = $validated['media_duration'] ?? null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaType = $file->getMimeType();

            // Infer message type from MIME when not explicitly set
            if (!isset($validated['message_type'])) {
                if (str_starts_with($mediaType, 'image/')) {
                    $resolvedType = 'image';
                } elseif (str_starts_with($mediaType, 'audio/')) {
                    $resolvedType = 'audio';
                } elseif (str_starts_with($mediaType, 'video/')) {
                    $resolvedType = 'video';
                } else {
                    $resolvedType = 'file';
                }
            }

            // If client specified a type, ensure it matches the detected type (for media)
            if (isset($validated['message_type'])) {
                $detectedForCompare = str_starts_with($mediaType, 'image/') ? 'image'
                    : (str_starts_with($mediaType, 'audio/') ? 'audio'
                        : (str_starts_with($mediaType, 'video/') ? 'video' : 'file'));

                if ($validated['message_type'] !== $detectedForCompare) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'message_type' => ['message_type does not match uploaded media']
                        ]
                    ], 422);
                }
            }

            // Type-specific constraints
            $sizeKB = (int) ceil(($file->getSize() ?? 0) / 1024);
            $limits = [
                'image' => 5120,   // 5 MB
                'audio' => 3072,   // 3 MB
                'video' => 15360,  // 15 MB
                'file'  => 2048,   // 2 MB
            ];

            // Validate size by resolved type
            $cap = $limits[$resolvedType] ?? $limits['file'];
            if ($sizeKB > $cap) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'media' => ["{$resolvedType} exceeds maximum size of {$cap} KB"]
                    ]
                ], 422);
            }

            // Validate allowed MIME for images specifically to block disguised executables
            if ($resolvedType === 'image') {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mediaType, $allowed, true)) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'media' => ['Unsupported image type']
                        ]
                    ], 422);
                }
            }

            // Clamp duration for audio/video
            if ($resolvedType === 'audio') {
                $duration = (int)($duration ?? 1);
                if ($duration < 1 || $duration > 120) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'media_duration' => ['Audio duration must be between 1 and 120 seconds']
                        ]
                    ], 422);
                }
            } elseif ($resolvedType === 'video') {
                $duration = (int)($duration ?? 1);
                if ($duration < 1 || $duration > 60) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'media_duration' => ['Video duration must be between 1 and 60 seconds']
                        ]
                    ], 422);
                }
            } else {
                $duration = null; // Not applicable
            }

            // Store in public disk under messages/{$senderId}
            $stored = \App\Services\MediaUploadService::store($file, $senderId, $resolvedType);
            $mediaUrl = $stored['media_url'];
            $mediaType = $stored['media_type'];
            $thumbnailUrl = $stored['thumbnail_url'];
        }

        // Create the message
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $validated['content'] ?? '',
            'message_type' => $resolvedType,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'media_duration' => $duration,
            'thumbnail_url' => $thumbnailUrl,
            'sent_at' => now(),
        ]);

        // Update match last_message_at
        $match->last_message_at = now();
        $match->save();

        // Increment tier message count
        $tier = $match->relationshipTier;
        
        if (!$tier) {
            $tier = RelationshipTier::create([
                'match_id' => $match->id,
                'current_tier' => 'matched',
                'first_matched_at' => $match->created_at,
            ]);
        }

        $previousTier = $tier->current_tier;
        $tier->incrementMessages();
        $tierUpgraded = $tier->current_tier !== $previousTier;

        return response()->json([
            'message' => $message,
            'tier_update' => [
                'current_tier' => $tier->current_tier,
                'previous_tier' => $previousTier,
                'tier_upgraded' => $tierUpgraded,
                'messages_exchanged' => $tier->messages_exchanged,
            ]
        ], 201);
    }

    /**
     * Get conversation with a matched user
        *
        * @OA\Get(
        *   path="/messages/{userId}",
        *   tags={"Messages"},
        *   summary="Get conversation with a matched user",
        *   description="Returns paginated messages and other user's presence info.",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(
        *     response=200,
        *     description="Conversation",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="messages", type="array", @OA\Items(type="object")),
        *       @OA\Property(property="pagination", type="object"),
        *       @OA\Property(property="other_user", type="object",
        *         @OA\Property(property="id", type="integer"),
        *         @OA\Property(property="name", type="string"),
        *         @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true)
        *       )
        *     )
        *   ),
        *   @OA\Response(response=403, description="Conversation access blocked"),
        *   @OA\Response(response=404, description="No active match"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $currentUserId = Auth::id();

        // Block enforcement for fetching conversation
        if (Block::isBlockedBetween($currentUserId, $userId)) {
            return response()->json(['error' => 'Conversation access blocked'], 403);
        }

        // Verify match exists
        $match = UserMatch::where(function ($query) use ($currentUserId, $userId) {
            $query->where('user1_id', $currentUserId)->where('user2_id', $userId)
                  ->orWhere('user1_id', $userId)->where('user2_id', $currentUserId);
        })->where('is_active', true)->first();

        if (!$match) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        $messages = Message::between($currentUserId, $userId)
            ->orderBy('sent_at', 'asc')
            ->paginate(50);

        // Include other user's presence info
        $otherUser = User::find($userId);

        return response()->json([
            'messages' => $messages->items(),
            'pagination' => [
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
            ],
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'last_seen_at' => optional($otherUser->last_seen_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark message as read
        *
        * @OA\Post(
        *   path="/messages/{messageId}/read",
        *   tags={"Messages"},
        *   summary="Mark a message as read",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Marked read"),
        *   @OA\Response(response=403, description="Only receiver can mark"),
        *   @OA\Response(response=404, description="Message not found"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function markAsRead(int $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);
        $currentUserId = Auth::id();

        // Only the receiver can mark a message as read
        if ($message->receiver_id != $currentUserId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Idempotent: do not overwrite existing timestamp
        if (!$message->is_read) {
            $message->is_read = true;
            $message->read_at = now();
            $message->save();
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'is_read' => $message->is_read,
                'read_at' => $message->read_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Get unread message count
        *
        * @OA\Get(
        *   path="/messages/unread-count",
        *   tags={"Messages"},
        *   summary="Get count of unread messages",
        *   security={{"bearerAuth":{}}},
        *   @OA\Response(
        *     response=200,
        *     description="Unread count",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="unread_count", type="integer", example=3)
        *     )
        *   ),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function unreadCount(): JsonResponse
    {
        $userId = Auth::id();
        
        $count = Message::where('receiver_id', $userId)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
