<?php

namespace App\Http\Controllers;

use App\Models\GovernanceProposal;
use App\Models\GovernanceVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GovernanceController extends Controller
{
    /**
     * List all active proposals with vote counts.
     */
    public function index(): JsonResponse
    {
        $proposals = GovernanceProposal::withCount('votes')
            ->where('status', 'active')
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(function ($proposal) {
                // Calculate results summary
                $results = DB::table('governance_votes')
                    ->where('governance_proposal_id', $proposal->id)
                    ->select('option_index', DB::raw('SUM(token_weight) as total_weight'))
                    ->groupBy('option_index')
                    ->get();
                
                $proposal->results = $results;
                return $proposal;
            });

        return response()->json(['proposals' => $proposals]);
    }

    /**
     * Create a new proposal (Requires 100 FWB Tokens).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user->token_balance < 100) {
            return response()->json(['error' => 'You need at least 100 FWB Tokens to create a proposal.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:mod,policy,tech,treasury',
            'options' => 'required|array|min:2',
            'options.*' => 'string|max:50',
            'duration_days' => 'required|integer|min:1|max:30',
        ]);

        $proposal = GovernanceProposal::create([
            'creator_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'options' => $validated['options'],
            'starts_at' => now(),
            'expires_at' => now()->addDays($validated['duration_days']),
            'status' => 'active',
        ]);

        return response()->json(['proposal' => $proposal], 201);
    }

    /**
     * Cast a weighted vote.
     */
    public function vote(Request $request, int $proposalId): JsonResponse
    {
        $user = Auth::user();
        $proposal = GovernanceProposal::findOrFail($proposalId);

        if ($proposal->status !== 'active' || ($proposal->expires_at && $proposal->expires_at->isPast())) {
            return response()->json(['error' => 'Voting is closed for this proposal.'], 400);
        }

        $validated = $request->validate([
            'option_index' => 'required|integer|min:0',
        ]);

        if (!isset($proposal->options[$validated['option_index']])) {
            return response()->json(['error' => 'Invalid option selected.'], 422);
        }

        // Token-weighted voting logic: The user's current balance is their voting power
        // In a real system, we might "lock" these tokens during the vote
        $weight = $user->token_balance;

        $vote = GovernanceVote::updateOrCreate(
            ['user_id' => $user->id, 'governance_proposal_id' => $proposal->id],
            [
                'option_index' => $validated['option_index'],
                'token_weight' => $weight,
            ]
        );

        return response()->json([
            'message' => 'Vote cast successfully!',
            'weight_recorded' => $weight,
            'vote' => $vote
        ]);
    }
}
