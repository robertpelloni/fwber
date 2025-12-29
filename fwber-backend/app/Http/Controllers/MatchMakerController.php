<?php

namespace App\Http\Controllers;

use App\Services\MatchMakerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class MatchMakerController extends Controller
{
    protected $matchMakerService;

    public function __construct(MatchMakerService $matchMakerService)
    {
        $this->matchMakerService = $matchMakerService;
    }

    /**
     * Create a new match bounty.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBounty(Request $request)
    {
        $request->validate([
            'token_reward' => 'required|integer|min:10',
        ]);

        $user = $request->user();
        
        // Basic check if user has enough tokens (though we might deduct later, it's good to check)
        // Assuming user has a 'tokens' relationship or attribute. 
        // If not strictly enforced here, the service handles the logic.
        if ($user->tokens < $request->token_reward) {
             return response()->json(['message' => 'Insufficient tokens'], 402);
        }

        try {
            $bounty = $this->matchMakerService->createBounty($user, $request->token_reward);
            
            return response()->json([
                'message' => 'Bounty created successfully',
                'bounty' => $bounty,
                'share_url' => config('app.url') . '/bounty/' . $bounty->slug // Or frontend URL
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create bounty: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create bounty'], 500);
        }
    }

    /**
     * Suggest a candidate for a bounty.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggest(Request $request, $slug)
    {
        $request->validate([
            'candidate_id' => 'required|exists:users,id',
        ]);

        $matchmaker = $request->user();
        
        try {
            $bounty = $this->matchMakerService->getBountyBySlug($slug);
            
            if (!$bounty) {
                return response()->json(['message' => 'Bounty not found or inactive'], 404);
            }

            $candidate = User::findOrFail($request->candidate_id);

            $assist = $this->matchMakerService->suggestCandidate($bounty, $matchmaker, $candidate);
            
            return response()->json([
                'message' => 'Candidate suggested successfully!',
                'assist' => $assist
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to suggest candidate: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to suggest candidate: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified bounty.
     */
    public function showBounty($slug)
    {
        try {
            $bounty = $this->matchMakerService->getBountyBySlug($slug);
            
            if (!$bounty) {
                return response()->json(['message' => 'Bounty not found'], 404);
            }

            // Return bounty with user details (limited public profile)
            $bounty->load(['user.profile', 'user.photos']);

            return response()->json([
                'bounty' => $bounty
            ]);
        } catch (\Exception $e) {
             Log::error('Failed to fetch bounty: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching bounty'], 500);
        }
    }
}
