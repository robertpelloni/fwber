<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\ContentUnlock;
use App\Services\AIMatchingService;
use App\Services\AiWingmanService;
use App\Services\TokenDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MatchInsightsController extends Controller
{
    protected AIMatchingService $matchingService;
    protected AiWingmanService $wingmanService;
    protected TokenDistributionService $tokenService;

    public function __construct(
        AIMatchingService $matchingService,
        AiWingmanService $wingmanService,
        TokenDistributionService $tokenService
    )
    {
        $this->matchingService = $matchingService;
        $this->wingmanService = $wingmanService;
        $this->tokenService = $tokenService;
    }

    /**
     * Get compatibility insights for a match
     * 
     * @OA\Get(
     *   path="/matches/{id}/insights",
     *   tags={"Matches"},
     *   summary="Get match compatibility insights",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string"), description="Match ID or Target User ID"),
     *   @OA\Response(response=200, description="Compatibility breakdown"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();
        
        $targetUser = $this->resolveTargetUser($user, $id);

        if (!$targetUser) {
            return response()->json(['message' => 'User or Match not found'], 404);
        }

        // Check if unlocked
        $isUnlocked = ContentUnlock::where('user_id', $user->id)
            ->where('content_type', 'match_insight')
            ->where('content_id', $targetUser->id)
            ->exists();

        // Calculate score (always free)
        $insights = $this->matchingService->getCompatibilityBreakdown($user, $targetUser);
        
        if (!$isUnlocked) {
             return response()->json([
                'success' => true,
                'data' => [
                    'total_score' => $insights['total_score'],
                    'is_locked' => true,
                    'cost' => 10,
                    'preview_message' => 'Unlock detailed compatibility analysis and AI insights for 10 tokens.'
                ]
            ]);
        }

        // Generate AI explanation (Premium)
        $explanation = $this->wingmanService->generateMatchExplanation($user, $targetUser);
        $insights['ai_explanation'] = $explanation;
        $insights['is_locked'] = false;

        return response()->json([
            'success' => true,
            'data' => $insights
        ]);
    }

    /**
     * Unlock compatibility insights
     *
     * @OA\Post(
     *   path="/matches/{id}/insights/unlock",
     *   tags={"Matches"},
     *   summary="Unlock match insights",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string"), description="Match ID or Target User ID"),
     *   @OA\Response(response=200, description="Unlocked successfully"),
     *   @OA\Response(response=402, description="Insufficient tokens"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $targetUser = $this->resolveTargetUser($user, $id);

        if (!$targetUser) {
            return response()->json(['message' => 'User or Match not found'], 404);
        }

        // Check if already unlocked
        $exists = ContentUnlock::where('user_id', $user->id)
            ->where('content_type', 'match_insight')
            ->where('content_id', $targetUser->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already unlocked'], 200);
        }

        // Deduct tokens (10 tokens)
        try {
            $this->tokenService->spendTokens($user, 10, "Unlocked Match Insight");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Insufficient tokens'], 402);
        }

        // Record unlock
        ContentUnlock::create([
            'user_id' => $user->id,
            'content_type' => 'match_insight',
            'content_id' => $targetUser->id,
            'cost' => 10
        ]);

        return response()->json(['message' => 'Unlocked successfully', 'balance' => $user->token_balance]);
    }

    private function resolveTargetUser(User $user, string $id): ?User
    {
        // Try to find by Match ID first
        $match = UserMatch::find($id);

        if ($match) {
            // Verify user is part of the match
            if ($match->user1_id !== $user->id && $match->user2_id !== $user->id) {
                return null;
            }

            $targetUserId = ($match->user1_id === $user->id) ? $match->user2_id : $match->user1_id;
            return User::with('profile')->find($targetUserId);
        }

        // Try to find by User ID directly
        return User::with('profile')->find($id);
    }
}
