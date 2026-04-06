<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wingman\AnalyzeDraftRequest;
use App\Http\Requests\Wingman\AnalyzeQuirkRequest;
use App\Http\Requests\Wingman\RoastPublicRequest;
use App\Models\Message;
use App\Models\User;
use App\Models\ViralContent;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIceBreakers(Request $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        $suggestions = $this->wingmanService->generateIceBreakers($user, $match);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Get reply suggestions for an ongoing conversation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplySuggestions(Request $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        // Fetch last 10 messages
        $messages = Message::where(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
        })
            ->orWhere(function ($q) use ($user, $matchId) {
                $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc') // Oldest first for context
            ->take(10)
            ->get()
            ->map(function ($msg) {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeDraft(AnalyzeDraftRequest $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        // Fetch last 10 messages for context
        $messages = Message::where(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
        })
            ->orWhere(function ($q) use ($user, $matchId) {
                $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get()
            ->map(function ($msg) {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDateIdeas(Request $request, string $matchId)
    {
        $user = Auth::user();
        $match = User::findOrFail($matchId);

        // Check if they are matched
        $isMatched = \App\Models\UserMatch::where(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $user->id)->where('user2_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('user1_id', $matchId)->where('user2_id', $user->id);
        })->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        $location = $request->input('location'); // Optional location string
        $ideas = $this->wingmanService->suggestDateIdeas($user, $match, $location);

        return response()->json(['ideas' => $ideas]);
    }

    /**
     * Generate a roast or hype for an unauthenticated user (Public "Tease").
     * Rate limited by IP.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roastPublic(RoastPublicRequest $request)
    {
        $mode = $request->input('mode', 'roast');

        $result = $this->wingmanService->roastGeneric(
            $request->input('name'),
            $request->input('job'),
            $request->input('trait'),
            $mode
        );

        // We do NOT save this to ViralContent database to avoid cluttering DB with guest data.
        // The user must sign up to save/share it permanently.
        // However, to allow the "Share" preview to work, we can return a temporary structured response
        // or a signed temporary URL if we wanted. For now, just the text.

        return response()->json([
            'roast' => $result,
            'is_preview' => true,
            'cta' => 'Sign up to save this roast and share it with friends!',
        ]);
    }

    /**
     * Generate a roast or hype of the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function roastProfile(Request $request)
    {
        $user = Auth::user();
        $mode = $request->input('mode', 'roast');

        if (! in_array($mode, ['roast', 'hype'])) {
            $mode = 'roast';
        }

        $result = $this->wingmanService->roastProfile($user, $mode);

        $shareId = $this->saveViralContent($user, $mode, ['text' => $result]);

        return response()->json([
            'roast' => $result,
            'share_id' => $shareId,
        ]);
    }

    /**
     * Generate "Red Flags" and "Green Flags" for the authenticated user's profile.
     *
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
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *   path="/wingman/fortune",
     *   tags={"AI Wingman"},
     *   summary="Predict astrological fortune",
     *   description="Reads the user's astrological fortune.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Fortune predicted")
     * )
     */
    public function predictFortune(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->predictFortune($user);

        $shareId = $this->saveViralContent($user, 'fortune', ['text' => $result]);

        return response()->json([
            'fortune' => $result,
            'share_id' => $shareId,
        ]);
    }

    /**
     * Generate a "Cosmic Match" prediction for the authenticated user.
     *
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
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *   path="/wingman/nemesis",
     *   tags={"AI Wingman"},
     *   summary="Find a nemesis profile",
     *   description="Generates a nemesis profile based on clashing traits.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Nemesis found")
     * )
     */
    public function findNemesis(Request $request)
    {
        $user = Auth::user();
        $result = $this->wingmanService->findNemesis($user);

        $shareId = $this->saveViralContent($user, 'nemesis', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    /**
     * @OA\Post(
     *   path="/wingman/quirk-check",
     *   tags={"AI Wingman"},
     *   summary="Analyze a user quirk",
     *   description="Evaluates a specific quirk for green/beige/red flags.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"quirk"},
     *       @OA\Property(property="quirk", type="string", example="Bites string cheese")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Quirk analyzed")
     * )
     */
    public function checkQuirk(AnalyzeQuirkRequest $request)
    {
        $user = Auth::user();
        $quirk = $request->input('quirk');

        $result = $this->wingmanService->analyzeQuirk($quirk);

        // Save for sharing
        $shareId = $this->saveViralContent($user, 'quirk', array_merge($result, ['quirk' => $quirk]));

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    /**
     * Generate a deep AI-powered "Compatibility Audit" for a matched user.
     * Requires mutual match. Token-gated (5 FWB tokens).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *   path="/wingman/compatibility-audit/{targetId}",
     *   tags={"AI Wingman"},
     *   summary="Perform a full compatibility audit",
     *   description="Deeply audits the compatibility between the user and a match.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="targetId", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Audit completed"),
     *   @OA\Response(response=403, description="Must be matched to audit")
     * )
     */
    public function compatibilityAudit(Request $request, string $targetId)
    {
        $user = Auth::user();
        $target = User::findOrFail($targetId);

        // Verify mutual match
        $isMatched = \App\Models\UserMatch::where(function ($q) use ($user, $targetId) {
            $q->where('user1_id', $user->id)->where('user2_id', $targetId);
        })->orWhere(function ($q) use ($user, $targetId) {
            $q->where('user1_id', $targetId)->where('user2_id', $user->id);
        })->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You must be matched with this user to request a compatibility audit.'], 403);
        }

        $audit = $this->wingmanService->generateCompatibilityAudit($user, $target);

        $shareId = $this->saveViralContent($user, 'compatibility_audit', $audit);

        return response()->json(array_merge($audit, ['share_id' => $shareId]));
    }
}
