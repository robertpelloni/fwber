<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProximityArtifact;
use App\Models\ProximityArtifactComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProximityArtifactCommentController extends Controller
{
    /**
     * Display a listing of comments for an artifact.
     */
    public function index(Request $request, ProximityArtifact $artifact): JsonResponse
    {
        // Get all comments for this artifact, eager load the user and replies.
        // We'll organize them natively as a nested tree if needed, or flat.
        // Typically, we only fetch root comments and their immediate replies.
        $comments = $artifact->comments()
            ->with(['user:id,name,avatar_url', 'replies.user:id,name,avatar_url'])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($comments);
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, ProximityArtifact $artifact): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|uuid|exists:proximity_artifact_comments,id',
        ]);

        // Ensure parent comment belongs to the same artifact if provided
        if (!empty($validated['parent_id'])) {
            $parent = ProximityArtifactComment::findOrFail($validated['parent_id']);
            if ($parent->proximity_artifact_id !== $artifact->id) {
                return response()->json(['message' => 'Invalid parent comment.'], 422);
            }
        }

        $comment = $artifact->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Load user info to return immediately for optimistic UI
        $comment->load('user:id,name,avatar_url');

        return response()->json([
            'message' => 'Comment posted',
            'comment' => $comment
        ], 201);
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(Request $request, ProximityArtifactComment $comment): JsonResponse
    {
        // Only the owner of the comment can delete it
        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
