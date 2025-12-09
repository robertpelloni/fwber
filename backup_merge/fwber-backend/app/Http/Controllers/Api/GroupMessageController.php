<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGroupMessageRequest;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\GroupMessageRead;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GroupMessageController extends Controller
{
    /**
     * Send a message to a group
     *
     * @OA\Post(
     *   path="/groups/{groupId}/messages",
     *   tags={"Groups"},
     *   summary="Send a message to a group",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(property="content", type="string", maxLength=5000),
     *         @OA\Property(property="message_type", type="string", enum={"text","image","video","audio","file"}),
     *         @OA\Property(property="media", type="string", format="binary"),
     *         @OA\Property(property="media_duration", type="integer", minimum=1)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Message created"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function store(StoreGroupMessageRequest $request, int $groupId): JsonResponse
    {
        $validated = $request->validated();

        $senderId = Auth::id();
        $group = Group::findOrFail($groupId);

        // Verify sender is an active member
        if (!$group->hasMember($senderId)) {
            return response()->json(['error' => 'Not a member of this group'], 403);
        }

        // Check mute status
        $member = $group->activeMembers()->where('user_id', $senderId)->first();
        if ($member && $member->isCurrentlyMuted()) {
            return response()->json(['error' => 'You are muted until '.$member->muted_until?->toIso8601String()], 403);
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

        // Media handling (reusing logic from MessageController)
        $mediaUrl = null;
        $mediaType = null;
        $thumbnailUrl = null;
        $resolvedType = $validated['message_type'] ?? 'text';
        $duration = $validated['media_duration'] ?? null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaType = $file->getMimeType();

            // Infer type from MIME
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

            // Type-specific constraints
            $sizeKB = (int) ceil(($file->getSize() ?? 0) / 1024);
            $limits = [
                'image' => 5120,
                'audio' => 3072,
                'video' => 15360,
                'file'  => 2048,
            ];

            $cap = $limits[$resolvedType] ?? $limits['file'];
            if ($sizeKB > $cap) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'media' => ["{$resolvedType} exceeds maximum size of {$cap} KB"]
                    ]
                ], 422);
            }

            // Validate allowed MIME for images
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

            // Duration validation for audio/video
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
                $duration = null;
            }

            $stored = MediaUploadService::store($file, $senderId, $resolvedType);
            $mediaUrl = $stored['media_url'];
            $mediaType = $stored['media_type'];
            $thumbnailUrl = $stored['thumbnail_url'];
        }

        // Create the message
        $message = GroupMessage::create([
            'group_id' => $groupId,
            'sender_id' => $senderId,
            'content' => $validated['content'] ?? '',
            'message_type' => $resolvedType,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'media_duration' => $duration,
            'thumbnail_url' => $thumbnailUrl,
            'sent_at' => now(),
        ]);

        return response()->json(['message' => $message->load('sender')], 201);
    }

    /**
     * Get messages for a group
     *
     * @OA\Get(
     *   path="/groups/{groupId}/messages",
     *   tags={"Groups"},
     *   summary="Get group messages (paginated)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1)),
     *   @OA\Response(response=200, description="Messages returned"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index(int $groupId): JsonResponse
    {
        $userId = Auth::id();
        $group = Group::findOrFail($groupId);

        // Verify user is a member
        if (!$group->hasMember($userId)) {
            return response()->json(['error' => 'Not a member of this group'], 403);
        }

        $messages = GroupMessage::where('group_id', $groupId)
            ->notDeleted()
            ->with(['sender', 'reads'])
            ->orderBy('sent_at', 'asc')
            ->paginate(50);

        return response()->json([
            'messages' => $messages->items(),
            'pagination' => [
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Mark message as read (idempotent)
     *
     * @OA\Post(
     *   path="/group-messages/{messageId}/read",
     *   tags={"Groups"},
     *   summary="Mark group message as read",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Read status updated"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function markAsRead(int $messageId): JsonResponse
    {
        $message = GroupMessage::findOrFail($messageId);
        $userId = Auth::id();

        // Verify user is a member of the group
        if (!$message->group->hasMember($userId)) {
            return response()->json(['error' => 'Not a member of this group'], 403);
        }

        // Idempotent: only create if not already read
        GroupMessageRead::firstOrCreate(
            [
                'group_message_id' => $messageId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );

        return response()->json([
            'message' => [
                'id' => $message->id,
                'read_count' => $message->readCount(),
                'is_read_by_user' => true,
            ],
        ]);
    }

    /**
     * Get unread message count across all user's groups
     *
     * @OA\Get(
     *   path="/group-messages/unread-count",
     *   tags={"Groups"},
     *   summary="Get unread group message count",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Unread count returned")
     * )
     */
    public function unreadCount(): JsonResponse
    {
        $userId = Auth::id();
        
        // Get all group IDs user is a member of
        $groupIds = Group::whereHas('activeMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');

        // Count unread messages in those groups
        $count = GroupMessage::whereIn('group_id', $groupIds)
            ->where('sender_id', '!=', $userId) // Don't count own messages
            ->whereDoesntHave('reads', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
