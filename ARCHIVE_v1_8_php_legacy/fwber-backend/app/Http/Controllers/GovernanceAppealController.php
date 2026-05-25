<?php

namespace App\Http\Controllers;

use App\Models\GovernanceAppeal;
use App\Models\GovernanceProposal;
use App\Models\GlobalBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GovernanceAppealController extends Controller
{
    /**
     * Submit an appeal for a global ban.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 1. Verify they are actually banned
        $ban = GlobalBan::where('bannable_identifier', (string) $user->id)
            ->where('type', 'user')
            ->first();

        if (!$ban) {
            return response()->json(['error' => 'You are not currently banned.'], 400);
        }

        // 2. Check for existing pending appeals
        if (GovernanceAppeal::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return response()->json(['error' => 'You already have a pending appeal.'], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:50|max:2000',
        ]);

        // 3. Create the appeal record
        $appeal = GovernanceAppeal::create([
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'status' => 'proposal_created',
        ]);

        // 4. Automatically trigger a community unban proposal
        // Note: Appeals don't require the 100 FWB fee, but the community must vote
        $proposal = GovernanceProposal::create([
            'creator_id' => $user->id, // The user is the one proposing their own unban
            'title' => "Unban User: {$user->name}",
            'description' => "User has appealed their ban. Rationale: " . $validated['reason'],
            'category' => 'policy',
            'options' => ['Unban', 'Keep Banned'],
            'execution_payload' => [
                'action' => 'unban_user',
                'params' => ['user_id' => $user->id]
            ],
            'starts_at' => now(),
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $appeal->update(['proposal_id' => $proposal->id]);

        return response()->json([
            'message' => 'Your appeal has been submitted and a community proposal has been created.',
            'proposal_id' => $proposal->id
        ], 201);
    }
}
