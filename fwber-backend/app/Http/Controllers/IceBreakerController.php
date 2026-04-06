<?php

namespace App\Http\Controllers;

use App\Models\IceBreakerAnswer;
use App\Models\IceBreakerQuestion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IceBreakerController extends Controller
{
    /**
     * Get shuffled ice breaker questions for a match pair.
     * Returns 5 random questions, prioritizing unanswered ones.
     */
    public function getQuestions(Request $request): JsonResponse
    {
        $request->validate([
            'match_id' => 'required|exists:users,id',
        ]);

        $userId = Auth::id();
        $matchId = $request->input('match_id');

        // Get IDs of questions already answered by this user for this match
        $answeredIds = IceBreakerAnswer::where('user_id', $userId)
            ->where('match_user_id', $matchId)
            ->pluck('question_id');

        // Prioritize unanswered questions, then fill with answered ones
        $unanswered = IceBreakerQuestion::active()
            ->whereNotIn('id', $answeredIds)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // If fewer than 5 unanswered, pad with random answered ones
        if ($unanswered->count() < 5) {
            $remaining = 5 - $unanswered->count();
            $answered = IceBreakerQuestion::active()
                ->whereIn('id', $answeredIds)
                ->inRandomOrder()
                ->limit($remaining)
                ->get();
            $questions = $unanswered->merge($answered);
        } else {
            $questions = $unanswered;
        }

        // Mark which ones the user has already answered
        $questions->each(function ($q) use ($userId, $matchId) {
            $userAnswer = IceBreakerAnswer::where('user_id', $userId)
                ->where('question_id', $q->id)
                ->where('match_user_id', $matchId)
                ->first();

            $matchAnswer = IceBreakerAnswer::where('user_id', $matchId)
                ->where('question_id', $q->id)
                ->where('match_user_id', $userId)
                ->first();

            $q->user_answered = $userAnswer !== null;
            $q->user_answer = $userAnswer?->answer;
            $q->match_answered = $matchAnswer !== null;
            // Only reveal match answer if both have answered
            $q->match_answer = ($userAnswer && $matchAnswer) ? $matchAnswer->answer : null;
            $q->is_revealed = $userAnswer !== null && $matchAnswer !== null;
        });

        return response()->json([
            'questions' => $questions,
            'meta' => [
                'total_answered' => $answeredIds->count(),
                'total_questions' => IceBreakerQuestion::active()->count(),
            ],
        ]);
    }

    /**
     * Submit an answer to an ice breaker question.
     */
    public function submitAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:ice_breaker_questions,id',
            'match_id' => 'required|exists:users,id',
            'answer' => 'required|string|max:500',
        ]);

        $userId = Auth::id();
        $matchId = $request->input('match_id');
        $questionId = $request->input('question_id');

        // Prevent self-matching
        if ($userId == $matchId) {
            return response()->json(['error' => 'Cannot answer for yourself'], 422);
        }

        // Create or update the answer
        $answer = IceBreakerAnswer::updateOrCreate(
            [
                'user_id' => $userId,
                'question_id' => $questionId,
                'match_user_id' => $matchId,
            ],
            [
                'answer' => $request->input('answer'),
            ]
        );

        // Check if the match has also answered → mutual reveal
        $matchAnswer = IceBreakerAnswer::where('user_id', $matchId)
            ->where('question_id', $questionId)
            ->where('match_user_id', $userId)
            ->first();

        $isRevealed = $matchAnswer !== null;

        return response()->json([
            'answer' => $answer,
            'is_revealed' => $isRevealed,
            'match_answer' => $isRevealed ? $matchAnswer->answer : null,
            'question' => IceBreakerQuestion::find($questionId),
        ]);
    }

    /**
     * Get all answered ice breaker cards for a match pair.
     */
    public function getAnswers(Request $request, string $matchId): JsonResponse
    {
        $userId = Auth::id();

        $userAnswers = IceBreakerAnswer::where('user_id', $userId)
            ->where('match_user_id', $matchId)
            ->with('question')
            ->get();

        $matchAnswers = IceBreakerAnswer::where('user_id', $matchId)
            ->where('match_user_id', $userId)
            ->with('question')
            ->get()
            ->keyBy('question_id');

        $cards = $userAnswers->map(function ($ua) use ($matchAnswers) {
            $matchAnswer = $matchAnswers->get($ua->question_id);
            $isRevealed = $matchAnswer !== null;

            return [
                'question_id' => $ua->question_id,
                'question' => $ua->question->question,
                'category' => $ua->question->category,
                'emoji' => $ua->question->emoji,
                'user_answer' => $ua->answer,
                'match_answer' => $isRevealed ? $matchAnswer->answer : null,
                'is_revealed' => $isRevealed,
                'answered_at' => $ua->created_at,
            ];
        });

        return response()->json([
            'cards' => $cards,
            'meta' => [
                'total_answered' => $userAnswers->count(),
                'total_revealed' => $cards->where('is_revealed', true)->count(),
            ],
        ]);
    }
}
