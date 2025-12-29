<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MatchBounty;
use App\Models\MatchAssist;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchBountyController extends Controller
{
    protected $tokenDistributionService;

    public function __construct(TokenDistributionService $tokenDistributionService)
    {
        $this->tokenDistributionService = $tokenDistributionService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token_reward' => 'required|integer|min:10',
            'description' => 'nullable|string|max:500',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $user = $request->user();
        $tokenReward = $request->token_reward;

        // Ensure user has enough tokens
        if ($user->token_balance < $tokenReward) {
            return response()->json(['message' => 'Insufficient token balance.'], 402);
        }

        try {
            return DB::transaction(function () use ($user, $request, $tokenReward) {
                // Deduct tokens (Escrow)
                $this->tokenDistributionService->spendTokens(
                    $user,
                    $tokenReward,
                    "Escrow for Match Bounty creation"
                );

                $slug = Str::random(8);
                while (MatchBounty::where('slug', $slug)->exists()) {
                    $slug = Str::random(8);
                }

                $bounty = MatchBounty::create([
                    'user_id' => $user->id,
                    'slug' => $slug,
                    'token_reward' => $tokenReward,
                    'description' => $request->description,
                    'status' => 'active',
                    'expires_at' => $request->expires_in_days ? now()->addDays($request->expires_in_days) : null,
                ]);

                return response()->json([
                    'message' => 'Bounty created successfully',
                    'bounty' => $bounty
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Failed to create match bounty: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create bounty.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $bounty = MatchBounty::with(['user:id,name,avatar_url'])->where('slug', $slug)->firstOrFail();

        return response()->json([
            'bounty' => $bounty
        ]);
    }

    /**
     * Suggest a candidate for the bounty.
     */
    public function suggest(Request $request, $slug)
    {
        $request->validate([
            'candidate_id' => 'required|exists:users,id',
        ]);

        $bounty = MatchBounty::where('slug', $slug)->firstOrFail();
        
        if ($bounty->status !== 'active') {
            return response()->json(['message' => 'This bounty is no longer active.'], 400);
        }

        if ($bounty->expires_at && $bounty->expires_at->isPast()) {
            return response()->json(['message' => 'This bounty has expired.'], 400);
        }

        $matchmaker = $request->user();
        $candidateId = $request->candidate_id;

        // Prevent self-suggestion if needed, though arguably funny? Let's block it for now.
        if ($candidateId == $bounty->user_id) {
            return response()->json(['message' => 'You cannot suggest the bounty creator to themselves.'], 400);
        }
        
        // Prevent matchmaker from suggesting themselves to the bounty creator?
        // Actually, maybe that's allowed? "I'm the match!"
        // For now, let's allow it unless specific logic forbids.

        // Check if suggestion already exists
        $existing = MatchAssist::where('matchmaker_id', $matchmaker->id)
            ->where('subject_id', $candidateId) // The candidate being suggested
            ->where('target_id', $bounty->user_id) // The bounty creator
            ->first();

        if ($existing) {
             return response()->json(['message' => 'You have already suggested this candidate.'], 409);
        }

        $assist = MatchAssist::create([
            'matchmaker_id' => $matchmaker->id,
            'subject_id' => $candidateId,
            'target_id' => $bounty->user_id,
            'status' => 'viewed', // Default status
            'match_bounty_id' => $bounty->id,
        ]);

        // In a real implementation, we would likely link the MatchAssist to the MatchBounty
        // so we know which suggestion claimed the reward. 
        // For this step, we'll assume the MatchAssist creation is enough to trigger the notification flow later.

        return response()->json([
            'message' => 'Candidate suggested successfully!',
            'assist' => $assist
        ], 201);
    }
}
