<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BlockController extends Controller
{
    /**
     * @OA\Post(
     *   path="/blocks",
     *   tags={"Safety"},
     *   summary="Block a user",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer")
     *     )
     *   ),
     *   @OA\Response(response=200, description="User blocked"),
     *   @OA\Response(response=422, description="Cannot block yourself")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $blockedId = $request->user_id;
        $blocker = Auth::user();

        if ($blocker->id == $blockedId) {
            return response()->json(['message' => 'Cannot block yourself'], 422);
        }

        if (!$blocker->blockedUsers()->where('blocked_id', $blockedId)->exists()) {
            $blocker->blockedUsers()->attach($blockedId);
        }

        return response()->json(['message' => 'User blocked successfully']);
    }

    /**
     * @OA\Delete(
     *   path="/blocks/{userId}",
     *   tags={"Safety"},
     *   summary="Unblock a user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="User unblocked")
     * )
     */
    public function destroy($userId)
    {
        $blocker = Auth::user();
        $blocker->blockedUsers()->detach($userId);

        return response()->json(['message' => 'User unblocked successfully']);
    }

    /**
     * @OA\Get(
     *   path="/blocks",
     *   tags={"Safety"},
     *   summary="List blocked users",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of blocked users")
     * )
     */
    public function index()
    {
        $blocked = Auth::user()->blockedUsers()->get(['users.id', 'users.name']);
        return response()->json(['data' => $blocked]);
    }
}
