<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Services\AiWingmanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiWingmanController extends Controller
{
    protected AiWingmanService $wingmanService;

    public function __construct(AiWingmanService $wingmanService)
    {
        $this->wingmanService = $wingmanService;
    }

    /**
     * Get ice breaker suggestions for a specific match.
     *
     * @param Request $request
     * @param string $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIceBreakers(Request $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (!$isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        $suggestions = $this->wingmanService->generateIceBreakers($user, $match);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Get reply suggestions for an ongoing conversation.
     *
     * @param Request $request
     * @param string $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplySuggestions(Request $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (!$isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        // Fetch last 10 messages
        $messages = Message::where(function($q) use ($user, $matchId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
            })
            ->orWhere(function($q) use ($user, $matchId) {
                $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc') // Oldest first for context
            ->take(10)
            ->get()
            ->map(function($msg) {
                return [
                    'sender_id' => $msg->sender_id,
                    'content' => $msg->content
                ];
            })
            ->toArray();

        if (empty($messages)) {
            // If no messages, fallback to ice breakers
            $suggestions = $this->wingmanService->generateIceBreakers($user, $match);
        } else {
            $suggestions = $this->wingmanService->generateReplySuggestions($user, $match, $messages);
        }

        return response()->json(['suggestions' => $suggestions]);
    }
}
