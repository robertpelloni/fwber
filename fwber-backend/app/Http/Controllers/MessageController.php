<?php

namespace App\Http\Controllers;

use App\Domain\Core\EventSourcing\EventStore;
use App\Events\Messaging\MessageSent;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Models\Block;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(
        private readonly EventStore $eventStore
    ) {}

    /**
     * Send a message to a matched user.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $senderId = Auth::id();
        $receiverId = $validated['receiver_id'];

        // Block enforcement
        if (Block::isBlockedBetween($senderId, (int) $receiverId)) {
            return response()->json(['error' => 'Messaging blocked between users'], 403);
        }

        // Verify match exists and is active
        $match = UserMatch::where(function ($query) use ($senderId, $receiverId) {
            $query->where('user1_id', $senderId)->where('user2_id', (int) $receiverId)
                ->orWhere('user1_id', (int) $receiverId)->where('user2_id', $senderId);
        })->where('is_active', true)->first();

        if (! $match) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        $resolvedType = $validated['message_type'] ?? 'text';
        $mediaUrl = null;
        $mediaType = null;

        // Message persistence
        $message = Message::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'body' => $validated['content'] ?? '',
            'type' => $resolvedType,
            'is_encrypted' => $validated['is_encrypted'] ?? false,
            'sent_at' => now(),
        ]);

        // --- EVENT SOURCING ---
        try {
            $aggregateId = (string) $match->id;
            $currentVersion = $this->eventStore->getCurrentVersion($aggregateId, 'Chatroom');
            $event = new MessageSent(
                $aggregateId,
                (int) $senderId,
                (int) $receiverId,
                (string) ($validated['content'] ?? ''),
                (string) $resolvedType,
                json_encode(['message_id' => $message->id])
            );
            $this->eventStore->append($event, 'Chatroom', $currentVersion + 1);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('EventStore failed for message', ['error' => $e->getMessage()]);
        }
        // ----------------------

        return response()->json(['message' => $message], 201);
    }

    /**
     * Get conversation with a matched user.
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $currentUserId = Auth::id();

        if (Block::isBlockedBetween($currentUserId, $userId)) {
            return response()->json(['error' => 'Conversation access blocked'], 403);
        }

        $match = UserMatch::where(function ($query) use ($currentUserId, $userId) {
            $query->where('user1_id', $currentUserId)->where('user2_id', $userId)
                ->orWhere('user1_id', $userId)->where('user2_id', $currentUserId);
        })->where('is_active', true)->first();

        if (! $match) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        $messages = Message::where(function($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })->orWhere(function($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        $otherUser = User::find($userId);

        return response()->json([
            'messages' => $messages->items(),
            'pagination' => [
                'total' => $messages->total(),
                'current_page' => $messages->currentPage(),
            ],
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'last_active_at' => optional($otherUser->last_active_at)->toIso8601String(),
            ],
        ]);
    }

    public function markAsRead(int $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);
        if ($message->receiver_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update(['read_at' => now()]);

        return response()->json(['message' => 'Read']);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Message::where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function syncBatch(Request $request): JsonResponse
    {
        // Simplest possible sync for core
        return response()->json(['success' => true]);
    }
}
