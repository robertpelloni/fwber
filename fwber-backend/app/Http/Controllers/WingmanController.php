<?php

namespace App\Http\Controllers;

use App\Services\MatchMakerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WingmanController extends Controller
{
    protected MatchMakerService $matchMakerService;

    public function __construct(MatchMakerService $matchMakerService)
    {
        $this->matchMakerService = $matchMakerService;
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
    public function recordAssist(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:users,id',
            'wingman_id' => 'required|exists:users,id',
        ]);

        $targetId = auth()->id();
        $subjectId = $request->subject_id;
        $wingmanId = $request->wingman_id;

        $this->matchMakerService->recordAssist($wingmanId, $subjectId, $targetId);

        return response()->json(['message' => 'Assist recorded']);
    }
}
