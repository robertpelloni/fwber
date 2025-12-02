<?php

namespace App\Http\Controllers;

use App\Models\Chatroom;
use App\Models\ChatroomMessage;
use App\Models\ChatroomMessageReaction;
use App\Services\ContentModerationService;
use App\Services\TelemetryService;
use App\Http\Requests\StoreChatroomMessageRequest;
use App\Http\Requests\ReactToChatroomMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatroomMessageController extends Controller
{
    protected $contentModeration;
    protected $telemetry;

    public function __construct(ContentModerationService $contentModeration, TelemetryService $telemetry)
    {
        $this->contentModeration = $contentModeration;
        $this->telemetry = $telemetry;
    }

    /**
     * Get messages for a chatroom
     *
     * @OA\Get(
     *   path="/chatrooms/{chatroomId}/messages",
     *   tags={"Chatrooms"},
     *   summary="List chatroom messages",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="pinned", in="query", required=false, @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="announcements", in="query", required=false, @OA\Schema(type="boolean")),
    *   @OA\Response(response=200, description="Paginated messages", @OA\JsonContent(ref="#/components/schemas/PaginatedChatMessages")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request, int $chatroomId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $query = $chatroom->messages()->notDeleted();

        // Filter by message type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter pinned messages
        if ($request->has('pinned')) {
            $query->where('is_pinned', $request->boolean('pinned'));
        }

        // Filter announcements
        if ($request->has('announcements')) {
            $query->where('is_announcement', $request->boolean('announcements'));
        }

        $messages = $query->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * Send a message to a chatroom
     *
     * @OA\Post(
     *   path="/chatrooms/{chatroomId}/messages",
     *   tags={"Chatrooms"},
     *   summary="Send message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"content"},
     *     @OA\Property(property="content", type="string", maxLength=2000),
     *     @OA\Property(property="type", type="string", enum={"text","image","file","announcement"}),
     *     @OA\Property(property="parent_id", type="integer", nullable=true),
     *     @OA\Property(property="metadata", type="object")
     *   )),
    *   @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ChatMessage")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
    *   @OA\Response(response=422, ref="#/components/responses/ModerationError")
     * )
     */
    public function store(StoreChatroomMessageRequest $request, int $chatroomId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        // Check if user is muted
        $member = $chatroom->members()->where('user_id', Auth::id())->first();
        if ($member && $member->pivot->is_muted) {
            return response()->json(['message' => 'You are muted in this chatroom'], 403);
        }

        // Validation handled by StoreChatroomMessageRequest

        // Content moderation
        $moderationResult = $this->contentModeration->moderateContent($request->content, [
            'user_id' => Auth::id(),
            'content_type' => 'chatroom_message',
            'chatroom_id' => $chatroomId,
        ]);

        if ($moderationResult['flagged']) {
            // Emit telemetry for moderation action
            $this->telemetry->emit('moderation.flagged', [
                'user_id' => Auth::id(),
                'content_type' => 'chatroom_message',
                'chatroom_id' => $chatroomId,
                'reason' => $moderationResult['reason'] ?? 'Inappropriate content',
                'severity' => $moderationResult['severity'] ?? 'medium',
            ]);

            return response()->json([
                'message' => 'Message blocked by content moderation',
                'reason' => $moderationResult['reason'] ?? 'Inappropriate content',
            ], 422);
        }

        // Create message
        $message = ChatroomMessage::create([
            'chatroom_id' => $chatroomId,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'type' => $request->get('type', 'text'),
            'metadata' => $request->metadata ?? [],
            'is_edited' => false,
            'is_deleted' => false,
            'is_pinned' => false,
            'is_announcement' => $request->get('type') === 'announcement',
            'reaction_count' => 0,
            'reply_count' => 0,
        ]);

        // Update chatroom activity
        $chatroom->updateActivity();
        $chatroom->increment('message_count');

        // If this is a reply, increment parent message reply count
        if ($request->parent_id) {
            ChatroomMessage::where('id', $request->parent_id)->increment('reply_count');
        }

        // Load relationships
        $message->load(['user', 'reactions', 'mentions.mentionedUser']);

        Log::info('Chatroom message sent', [
            'chatroom_id' => $chatroomId,
            'user_id' => Auth::id(),
            'message_id' => $message->id,
            'type' => $message->type,
        ]);

        // Emit telemetry
        $this->telemetry->emit('message.sent', [
            'chatroom_id' => $chatroomId,
            'user_id' => Auth::id(),
            'message_id' => $message->id,
            'type' => $message->type,
            'is_reply' => !is_null($request->parent_id),
            'content_length' => strlen($request->content),
        ]);

        return response()->json($message, 201);
    }

    /**
     * Get a specific message
     *
     * @OA\Get(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}",
     *   tags={"Chatrooms"},
     *   summary="Get message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Message", @OA\JsonContent(ref="#/components/schemas/ChatMessage")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()
            ->with(['user', 'reactions', 'mentions.mentionedUser', 'parent.user'])
            ->findOrFail($messageId);

        return response()->json($message);
    }

    /**
     * Edit a message
     *
     * @OA\Put(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}",
     *   tags={"Chatrooms"},
     *   summary="Edit message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"content"},
     *     @OA\Property(property="content", type="string", maxLength=2000)
     *   )),
    *   @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ChatMessage")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
    *   @OA\Response(response=422, ref="#/components/responses/ModerationError")
     * )
     */
    public function update(Request $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        // Check if user can edit this message
        if ($message->user_id !== Auth::id() && !$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You can only edit your own messages'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        // Content moderation
        $moderationResult = $this->contentModeration->moderateContent($request->content, [
            'user_id' => Auth::id(),
            'content_type' => 'chatroom_message_edit',
            'chatroom_id' => $chatroomId,
            'original_message_id' => $messageId,
        ]);

        if ($moderationResult['flagged']) {
            return response()->json([
                'message' => 'Message blocked by content moderation',
                'reason' => $moderationResult['reason'] ?? 'Inappropriate content',
            ], 422);
        }

        $message->edit($request->content);

        Log::info('Chatroom message edited', [
            'chatroom_id' => $chatroomId,
            'message_id' => $messageId,
            'user_id' => Auth::id(),
        ]);

        return response()->json($message->load(['user', 'reactions', 'mentions.mentionedUser']));
    }

    /**
     * Delete a message
     *
     * @OA\Delete(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}",
     *   tags={"Chatrooms"},
     *   summary="Delete message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        // Check if user can delete this message
        if ($message->user_id !== Auth::id() && !$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You can only delete your own messages'], 403);
        }

        $message->softDelete();

        Log::info('Chatroom message deleted', [
            'chatroom_id' => $chatroomId,
            'message_id' => $messageId,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Message deleted successfully']);
    }

    /**
     * Add reaction to a message
     *
     * @OA\Post(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}/reactions",
     *   tags={"Chatrooms"},
     *   summary="Add reaction",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"emoji"},
     *     @OA\Property(property="emoji", type="string", maxLength=10)
     *   )),
    *   @OA\Response(response=200, description="Added", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function addReaction(ReactToChatroomMessageRequest $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        // Validation handled by ReactToChatroomMessageRequest

        $message->addReaction(Auth::user(), $request->emoji);

        return response()->json(['message' => 'Reaction added successfully']);
    }

    /**
     * Remove reaction from a message
     *
     * @OA\Delete(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}/reactions",
     *   tags={"Chatrooms"},
     *   summary="Remove reaction",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"emoji"},
     *     @OA\Property(property="emoji", type="string", maxLength=10)
     *   )),
    *   @OA\Response(response=200, description="Removed", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function removeReaction(ReactToChatroomMessageRequest $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        // Validation handled by ReactToChatroomMessageRequest

        $message->removeReaction(Auth::user(), $request->emoji);

        return response()->json(['message' => 'Reaction removed successfully']);
    }

    /**
     * Pin a message (moderator only)
     *
     * @OA\Post(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}/pin",
     *   tags={"Chatrooms"},
     *   summary="Pin message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Pinned", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function pin(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to pin messages'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);
        $message->pin();

        return response()->json(['message' => 'Message pinned successfully']);
    }

    /**
     * Unpin a message (moderator only)
     *
     * @OA\Delete(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}/pin",
     *   tags={"Chatrooms"},
     *   summary="Unpin message",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Unpinned", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function unpin(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to unpin messages'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);
        $message->unpin();

        return response()->json(['message' => 'Message unpinned successfully']);
    }

    /**
     * Get pinned messages for a chatroom
     *
     * @OA\Get(
     *   path="/chatrooms/{chatroomId}/messages/pinned",
     *   tags={"Chatrooms"},
     *   summary="Pinned messages",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="List", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ChatMessage"))),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function pinned(int $chatroomId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $pinnedMessages = $chatroom->messages()
            ->pinned()
            ->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pinnedMessages);
    }

    /**
     * Get message replies
     *
     * @OA\Get(
     *   path="/chatrooms/{chatroomId}/messages/{messageId}/replies",
     *   tags={"Chatrooms"},
     *   summary="Message replies",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="chatroomId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="messageId", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="List", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ChatMessage"))),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function replies(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);
        $replies = $message->replies()
            ->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($replies);
    }
}
