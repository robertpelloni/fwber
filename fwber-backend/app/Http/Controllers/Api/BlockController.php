<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use Illuminate\Http\Request;
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
     *       required={"blocked_id"},
     *       @OA\Property(property="blocked_id", type="integer")
     *     )
     *   ),
     *   @OA\Response(response=200, description="User blocked"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'blocked_id' => 'required|integer|exists:users,id'
        ]);
        if ($data['blocked_id'] == Auth::id()) {
            return response()->json(['error' => 'Cannot block yourself'], 422);
        }
        $block = Block::firstOrCreate([
            'blocker_id' => Auth::id(),
            'blocked_id' => $data['blocked_id']
        ]);
        return response()->json(['data' => $block]);
    }

    /**
     * @OA\Delete(
     *   path="/blocks/{blockedId}",
     *   tags={"Safety"},
     *   summary="Unblock a user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="blockedId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="User unblocked")
     * )
     */
    public function destroy(int $blockedId)
    {
        Block::where('blocker_id', Auth::id())->where('blocked_id', $blockedId)->delete();
        return response()->json(['ok' => true]);
    }
}
