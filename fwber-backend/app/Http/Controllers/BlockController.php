<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreBlockRequest;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlockController extends Controller
{
    /**
     * @OA\Post(
     *   path="/blocks",
     *   tags={"Safety"},
     *   summary="Block a user",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *       required={"user_id"},
     *
     *       @OA\Property(property="user_id", type="integer")
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="User blocked"),
     *   @OA\Response(response=422, description="Cannot block yourself")
     * )
     */
    public function store(StoreBlockRequest $request)
    {
        $blockedId = $request->user_id;
        $blocker = Auth::user();

        if ($blocker->id == $blockedId) {
            return response()->json(['message' => 'Cannot block yourself'], 422);
        }

        if (! $blocker->blockedUsers()->where('blocked_id', $blockedId)->exists()) {
            $blocker->blockedUsers()->attach($blockedId);
            
            // --- SAFETY POLISH: Sever existing connections ---
            
            // 1. Deactivate any existing match
            DB::table('user_matches')
                ->where(function ($query) use ($blocker, $blockedId) {
                    $query->where('user1_id', $blocker->id)->where('user2_id', $blockedId)
                        ->orWhere('user1_id', $blockedId)->where('user2_id', $blocker->id);
                })
                ->update(['is_active' => false]);
                
            // 2. Remove any "like" match actions so they don't reappear in queues
            DB::table('match_actions')
                ->where(function ($query) use ($blocker, $blockedId) {
                    $query->where('user_id', $blocker->id)->where('target_user_id', $blockedId)
                        ->orWhere('user_id', $blockedId)->where('target_user_id', $blocker->id);
                })
                ->delete();
                
            // 3. Flush matching caches
            TaggedCache::flush(["matches_feed:user_{$blocker->id}"]);
            TaggedCache::flush(["matches_feed:user_{$blockedId}"]);
            TaggedCache::flush(["matches_list:user_{$blocker->id}"]);
            TaggedCache::flush(["matches_list:user_{$blockedId}"]);
        }

        return response()->json(['message' => 'User blocked successfully']);
    }

    /**
     * @OA\Delete(
     *   path="/blocks/{userId}",
     *   tags={"Safety"},
     *   summary="Unblock a user",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="User unblocked")
     * )
     */
    public function destroy($userId)
    {
        $blocker = Auth::user();
        $blocker->blockedUsers()->detach($userId);

        TaggedCache::flush(["matches_feed:user_{$blocker->id}"]);
        TaggedCache::flush(["matches_feed:user_{$userId}"]);
        TaggedCache::flush(["matches_list:user_{$blocker->id}"]);
        TaggedCache::flush(["matches_list:user_{$userId}"]);

        return response()->json(['message' => 'User unblocked successfully']);
    }

    /**
     * @OA\Get(
     *   path="/blocks",
     *   tags={"Safety"},
     *   summary="List blocked users",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(response=200, description="List of blocked users")
     * )
     */
    public function index()
    {
        $blocked = Auth::user()->blockedUsers()->get(['users.id', 'users.name']);

        return response()->json(['data' => $blocked]);
    }
}
