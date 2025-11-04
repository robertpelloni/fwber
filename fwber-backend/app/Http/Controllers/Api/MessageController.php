<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\UserMatch;
use App\Models\RelationshipTier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Send a message to a matched user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:5000',
            'message_type' => 'sometimes|string|in:text,image,video,audio',
        ]);

        $senderId = Auth::id();
        $receiverId = $validated['receiver_id'];

        // Find the match between these users
        $match = UserMatch::where(function ($query) use ($senderId, $receiverId) {
            $query->where('user1_id', $senderId)->where('user2_id', $receiverId)
                  ->orWhere('user1_id', $receiverId)->where('user2_id', $senderId);
        })->where('is_active', true)->first();

        if (!$match) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        // Create the message
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $validated['content'],
            'message_type' => $validated['message_type'] ?? 'text',
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
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $currentUserId = Auth::id();

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

        return response()->json($messages);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(int $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);

        if ($message->receiver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->is_read = true;
        $message->read_at = now();
        $message->save();

        return response()->json(['success' => true]);
    }

    /**
     * Get unread message count
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
