<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProximityArtifactInteractionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/proximity/artifacts/{id}/comment",
     *     tags={"Proximity"},
     *     summary="Add a comment to a proximity artifact",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", maxLength=500)
     *         )
     *     ),
     *     @OA\Response(response="201", description="Comment created")
     * )
     */
    public function comment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $artifact = ProximityArtifact::active()->findOrFail($id);

        $comment = $artifact->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user:id,first_name') // Include basic user info
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/proximity/artifacts/{id}/vote",
     *     tags={"Proximity"},
     *     summary="Vote on a proximity artifact",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vote"},
     *             @OA\Property(property="vote", type="integer", enum={1, -1, 0})
     *         )
     *     ),
     *     @OA\Response(response="200", description="Vote recorded")
     * )
     */
    public function vote(Request $request, $id)
    {
        $request->validate([
            'vote' => 'required|integer|in:-1,0,1',
        ]);

        $artifact = ProximityArtifact::active()->findOrFail($id);
        $user = $request->user();
        $voteValue = (int) $request->input('vote');

        if ($voteValue === 0) {
            // Remove vote
            $artifact->votes()->where('user_id', $user->id)->delete();
        } else {
            // Upsert vote
            $artifact->votes()->updateOrCreate(
                ['user_id' => $user->id],
                ['vote' => $voteValue]
            );
        }

        // Return updated aggregates
        $artifact->loadCount('comments');
        $artifact->loadSum('votes', 'vote');
        
        return response()->json([
            'message' => 'Vote recorded',
            'votes_sum_vote' => (int) $artifact->votes_sum_vote,
            'user_vote' => $voteValue
        ]);
    }
}
