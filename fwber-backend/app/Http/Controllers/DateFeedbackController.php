<?php

namespace App\Http\Controllers;

use App\Models\DateFeedback;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DateFeedbackController extends Controller
{
    /**
     * Submit feedback for a specific match.
     */
    public function store(Request $request, $matchId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string|max:1000',
            'safety_concerns' => 'boolean',
        ]);

        $user = $request->user();

        // 1. Verify the match exists and the user is part of it
        $match = UserMatch::where('id', $matchId)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })->firstOrFail();

        // 2. Identify the other user
        $subjectId = $match->user1_id === $user->id ? $match->user2_id : $match->user1_id;

        // 3. Ensure they haven't already submitted feedback
        $existing = DateFeedback::where('match_id', $match->id)
            ->where('reporting_user_id', $user->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'match_id' => ['You have already submitted feedback for this match.'],
            ]);
        }

        // 4. Create feedback
        $feedback = DateFeedback::create([
            'match_id' => $match->id,
            'reporting_user_id' => $user->id,
            'subject_user_id' => $subjectId,
            'rating' => $request->rating,
            'feedback_text' => $request->feedback_text,
            'safety_concerns' => $request->safety_concerns ?? false,
        ]);

        // If safety concerns are flagged, we could dispatch an event here for admin review.
        // event(new DateSafetyConcernFlagged($feedback));

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'feedback' => $feedback,
        ], 201);
    }

    /**
     * Check if the authenticated user has already submitted feedback for a match.
     */
    public function show(Request $request, $matchId)
    {
        $user = $request->user();

        // Optional: verify match ownership again, but simpler to just query the feedback directly
        $feedback = DateFeedback::where('match_id', $matchId)
            ->where('reporting_user_id', $user->id)
            ->first();

        if (!$feedback) {
            return response()->json(['submitted' => false], 404);
        }

        return response()->json([
            'submitted' => true,
            'feedback' => $feedback,
        ]);
    }
}
