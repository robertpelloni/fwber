<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Services\AiWingmanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\ViralContent;

class AiWingmanController extends Controller
{
    protected AiWingmanService $wingmanService;

    public function __construct(AiWingmanService $wingmanService)
    {
        $this->wingmanService = $wingmanService;
    }

    protected function saveViralContent(User $user, string $type, array $content): string
    {
        $viral = ViralContent::create([
            'user_id' => $user->id,
            'type' => $type,
            'content' => $content,
        ]);
        return $viral->id;
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
                    'content' => $msg->content,
                    'transcription' => $msg->transcription ?? null,
                    'message_type' => $msg->message_type ?? 'text',
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

    /**
     * Analyze a draft message and provide feedback.
     *
     * @param Request $request
     * @param string $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeDraft(Request $request, string $matchId)
    {
        $request->validate([
            'draft' => 'required|string|max:1000',
        ]);

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

        // Fetch last 10 messages for context
        $messages = Message::where(function($q) use ($user, $matchId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
            })
            ->orWhere(function($q) use ($user, $matchId) {
                $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get()
            ->map(function($msg) {
                return [
                    'sender_id' => $msg->sender_id,
                    'content' => $msg->content,
                    'transcription' => $msg->transcription ?? null,
                    'message_type' => $msg->message_type ?? 'text',
                ];
            })
            ->toArray();

        $analysis = $this->wingmanService->analyzeDraftMessage($user, $match, $request->input('draft'), $messages);

        return response()->json($analysis);
    }

    /**
     * Analyze the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileAnalysis(Request $request)
    {
        $user = Auth::user();
        $analysis = $this->wingmanService->analyzeProfile($user);

        return response()->json($analysis);
    }

    /**
     * Get date ideas for a specific match.
     *
     * @param Request $request
     * @param string $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDateIdeas(Request $request, string $matchId)
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

        $location = $request->input('location'); // Optional location string
        $ideas = $this->wingmanService->suggestDateIdeas($user, $match, $location);

        return response()->json(['ideas' => $ideas]);
    }

    /**
     * Generate a roast or hype of the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roastProfile(Request $request)
    {
        $user = Auth::user();
        $mode = $request->input('mode', 'roast');

        if (!in_array($mode, ['roast', 'hype'])) {
            $mode = 'roast';
        }

        $result = $this->wingmanService->roastProfile($user, $mode);

        $shareId = $this->saveViralContent($user, $mode, ['text' => $result]);

        return response()->json([
            'roast' => $result,
            'share_id' => $shareId
        ]);
    }

    /**
     * Generate "Red Flags" and "Green Flags" for the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkVibe(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->checkVibe($user);

        $shareId = $this->saveViralContent($user, 'vibe', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    /**
     * Generate a humorous "Dating Fortune" for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function predictFortune(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->predictFortune($user);

        $shareId = $this->saveViralContent($user, 'fortune', ['text' => $result]);

        return response()->json([
            'fortune' => $result,
            'share_id' => $shareId
        ]);
    }

    /**
     * Generate a "Cosmic Match" prediction for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCosmicMatch(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->predictCosmicMatch($user);

        $shareId = $this->saveViralContent($user, 'cosmic', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    /**
     * Generate a "Scientific Nemesis" profile for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findNemesis(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->findNemesis($user);

        $shareId = $this->saveViralContent($user, 'nemesis', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }
}
