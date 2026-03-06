<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProximityArtifact;
use App\Models\ProximityArtifactVote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProximityArtifactVoteController extends Controller
{
    /**
     * Cast or remove a vote on a proximity artifact.
     */
    public function vote(Request $request, ProximityArtifact $artifact): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|integer|in:-1,0,1',
        ]);

        $user = $request->user();
        $value = $validated['value'];

        if ($value === 0) {
            // Remove the vote
            ProximityArtifactVote::where('proximity_artifact_id', $artifact->id)
                ->where('user_id', $user->id)
                ->delete();
                
            return response()->json(['message' => 'Vote removed']);
        }

        // Upsert the vote (1 or -1)
        $vote = ProximityArtifactVote::updateOrCreate(
            [
                'proximity_artifact_id' => $artifact->id,
                'user_id' => $user->id,
            ],
            [
                'value' => $value,
            ]
        );

        return response()->json([
            'message' => 'Vote recorded',
            'vote' => $vote
        ]);
    }
}
