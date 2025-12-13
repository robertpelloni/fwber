<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMatch;
use App\Services\AIMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchInsightsController extends Controller
{
    protected AIMatchingService $matchingService;

    public function __construct(AIMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
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
        
        // Try to find by Match ID first
        $match = UserMatch::find($id);
        
        if ($match) {
            // Verify user is part of the match
            if ($match->user1_id !== $user->id && $match->user2_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            $targetUserId = ($match->user1_id === $user->id) ? $match->user2_id : $match->user1_id;
            $targetUser = User::with('profile')->find($targetUserId);
        } else {
            // Try to find by User ID (for potential matches or direct lookup)
            // In a real app, we might want to restrict this to only people you've matched with or are in your feed
            $targetUser = User::with('profile')->find($id);
        }

        if (!$targetUser) {
            return response()->json(['message' => 'User or Match not found'], 404);
        }

        $insights = $this->matchingService->getCompatibilityBreakdown($user, $targetUser);

        return response()->json([
            'success' => true,
            'data' => $insights
        ]);
    }
}
