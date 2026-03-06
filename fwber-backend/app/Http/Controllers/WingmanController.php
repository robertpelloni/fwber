<?php

namespace App\Http\Controllers;

use App\Services\MatchMakerService;
use App\Services\AIMatchingService;
use App\Models\UserMatch as MatchModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Social\RecordWingmanAssistRequest;

class WingmanController extends Controller
{
    protected MatchMakerService $matchMakerService;
    protected AIMatchingService $aiMatchingService;

    public function __construct(MatchMakerService $matchMakerService, AIMatchingService $aiMatchingService)
    {
        $this->matchMakerService = $matchMakerService;
        $this->aiMatchingService = $aiMatchingService;
    }

    /**
     * @OA\Post(
     *     path="/wingman/assist",
     *     tags={"Wingman"},
     *     summary="Record a wingman assist",
     *     description="Records that a user viewed a profile via a wingman link.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject_id", "wingman_id"},
     *             @OA\Property(property="subject_id", type="integer", example=42),
     *             @OA\Property(property="wingman_id", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assist recorded",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assist recorded")
     *         )
     *     )
     * )
     */
    public function recordAssist(RecordWingmanAssistRequest $request): JsonResponse
    {

        $targetId = auth()->id();
        $subjectId = $request->subject_id;
        $wingmanId = $request->wingman_id;

        $this->matchMakerService->recordAssist($wingmanId, $subjectId, $targetId);

        return response()->json(['message' => 'Assist recorded']);
    }

    /**
     * @OA\Get(
     *     path="/wingman/date-ideas/{match}",
     *     tags={"Wingman"},
     *     summary="Generate AI Date Ideas",
     *     description="Generates personalized date ideas for a specific matched pair.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="match",
     *         in="path",
     *         required=true,
     *         description="The target User ID of the match",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         required=false,
     *         description="Optional location context",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Forbidden (Users not matched)"),
     *     @OA\Response(response=404, description="Target user not found")
     * )
     */
    public function generateDateIdeas(Request $request, int $matchId): JsonResponse
    {
        $user1 = auth()->user();
        $user2 = User::with('profile')->findOrFail($matchId);

        // Security: Ensure they are actually matched
        $isMatched = MatchModel::where(function($q) use ($user1, $user2) {
                $q->where('user1_id', $user1->id)->where('user2_id', $user2->id);
            })->orWhere(function($q) use ($user1, $user2) {
                $q->where('user1_id', $user2->id)->where('user2_id', $user1->id);
            })->where('status', 'accepted')->exists();

        if (!$isMatched) {
            return response()->json(['message' => 'You are not matched with this user.'], 403);
        }

        $location = $request->query('location');
        $ideas = $this->aiMatchingService->generateDateIdeas($user1, $user2, $location);

        return response()->json($ideas);
    }
}
