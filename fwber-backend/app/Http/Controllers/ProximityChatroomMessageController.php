<?php

namespace App\Http\Controllers;

use App\Models\ProximityChatroom;
use App\Models\ProximityChatroomMessage;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProximityChatroomMessageController extends Controller
{
    protected $contentModeration;

    public function __construct(ContentModerationService $contentModeration)
    {
        $this->contentModeration = $contentModeration;
    }

    /**
     * Get messages for a proximity chatroom
     */
    public function index(Request $request, int $chatroomId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $query = $chatroom->messages()->notDeleted();

        // Filter by message type
        if ($request->has('type')) {
            $query->where('message_type', $request->type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by networking/social
        if ($request->has('networking_only')) {
            $query->where('is_networking', true);
        }

        if ($request->has('social_only')) {
            $query->where('is_social', true);
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
     * Send a message to a proximity chatroom
     */
    public function store(Request $request, int $chatroomId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        // Check if user is muted
        $member = $chatroom->members()->where('user_id', Auth::id())->first();
        if ($member && $member->pivot->is_muted) {
            return response()->json(['message' => 'You are muted in this chatroom'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
            'message_type' => 'nullable|in:text,image,file,announcement',
            'parent_id' => 'nullable|exists:proximity_chatroom_messages,id',
            'is_networking' => 'boolean',
            'is_social' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        // Content moderation
        $moderationResult = $this->contentModeration->moderateContent($request->content, [
            'user_id' => Auth::id(),
            'content_type' => 'proximity_chatroom_message',
            'chatroom_id' => $chatroomId,
        ]);

        if ($moderationResult['flagged']) {
            return response()->json([
                'message' => 'Message blocked by content moderation',
                'reason' => $moderationResult['reason'] ?? 'Inappropriate content',
            ], 422);
        }

        // Create message
        $message = ProximityChatroomMessage::create([
            'proximity_chatroom_id' => $chatroomId,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'message_type' => $request->get('message_type', 'text'),
            'metadata' => $request->metadata ?? [],
            'is_edited' => false,
            'is_deleted' => false,
            'is_pinned' => false,
            'is_announcement' => $request->get('message_type') === 'announcement',
            'is_networking' => $request->get('is_networking', false),
            'is_social' => $request->get('is_social', true),
            'reaction_count' => 0,
            'reply_count' => 0,
        ]);

        // Update chatroom activity
        $chatroom->updateActivity();
        $chatroom->increment('message_count');

        // If this is a reply, increment parent message reply count
        if ($request->parent_id) {
            ProximityChatroomMessage::where('id', $request->parent_id)->increment('reply_count');
        }

        // Load relationships
        $message->load(['user', 'reactions', 'mentions.mentionedUser']);

        Log::info('Proximity chatroom message sent', [
            'chatroom_id' => $chatroomId,
            'user_id' => Auth::id(),
            'message_id' => $message->id,
            'message_type' => $message->message_type,
            'is_networking' => $message->is_networking,
            'is_social' => $message->is_social,
        ]);

        return response()->json($message, 201);
    }

    /**
     * Get a specific message
     */
    public function show(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

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
     */
    public function update(Request $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

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
            'is_networking' => 'boolean',
            'is_social' => 'boolean',
        ]);

        // Content moderation
        $moderationResult = $this->contentModeration->moderateContent($request->content, [
            'user_id' => Auth::id(),
            'content_type' => 'proximity_chatroom_message_edit',
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
        
        // Update networking/social flags
        if ($request->has('is_networking')) {
            $message->update(['is_networking' => $request->is_networking]);
        }
        
        if ($request->has('is_social')) {
            $message->update(['is_social' => $request->is_social]);
        }

        Log::info('Proximity chatroom message edited', [
            'chatroom_id' => $chatroomId,
            'message_id' => $messageId,
            'user_id' => Auth::id(),
        ]);

        return response()->json($message->load(['user', 'reactions', 'mentions.mentionedUser']));
    }

    /**
     * Delete a message
     */
    public function destroy(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        // Check if user can delete this message
        if ($message->user_id !== Auth::id() && !$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You can only delete your own messages'], 403);
        }

        $message->softDelete();

        Log::info('Proximity chatroom message deleted', [
            'chatroom_id' => $chatroomId,
            'message_id' => $messageId,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Message deleted successfully']);
    }

    /**
     * Add reaction to a message
     */
    public function addReaction(Request $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $message->addReaction(Auth::user(), $request->emoji);

        return response()->json(['message' => 'Reaction added successfully']);
    }

    /**
     * Remove reaction from a message
     */
    public function removeReaction(Request $request, int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);

        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $message->removeReaction(Auth::user(), $request->emoji);

        return response()->json(['message' => 'Reaction removed successfully']);
    }

    /**
     * Pin a message (moderator only)
     */
    public function pin(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to pin messages'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);
        $message->pin();

        return response()->json(['message' => 'Message pinned successfully']);
    }

    /**
     * Unpin a message (moderator only)
     */
    public function unpin(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to unpin messages'], 403);
        }

        $message = $chatroom->messages()->findOrFail($messageId);
        $message->unpin();

        return response()->json(['message' => 'Message unpinned successfully']);
    }

    /**
     * Get pinned messages for a proximity chatroom
     */
    public function pinned(int $chatroomId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

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
     */
    public function replies(int $chatroomId, int $messageId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

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

    /**
     * Get networking messages
     */
    public function networking(int $chatroomId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $networkingMessages = $chatroom->messages()
            ->networking()
            ->notDeleted()
            ->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($networkingMessages);
    }

    /**
     * Get social messages
     */
    public function social(int $chatroomId): JsonResponse
    {
        $chatroom = ProximityChatroom::findOrFail($chatroomId);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $socialMessages = $chatroom->messages()
            ->social()
            ->notDeleted()
            ->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($socialMessages);
    }
}
