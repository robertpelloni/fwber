<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RespondToFriendRequest;
use App\Http\Requests\Api\SendFriendRequest;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Friends",
 *     description="API Endpoints for managing friends"
 * )
 */
class FriendController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/friends",
     *     tags={"Friends"},
     *     summary="Get friends list",
     *     description="Returns the list of authenticated user's friends.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     )
     * )
     */
    public function getFriends()
    {
        $friends = Auth::user()->friends;
        return response()->json($friends);
    }

    /**
     * @OA\Get(
     *     path="/api/friends/requests",
     *     tags={"Friends"},
     *     summary="Get pending friend requests",
     *     description="Returns the list of pending friend requests for the authenticated user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Friend"))
     *     )
     * )
     */
    public function getFriendRequests()
    {
        $friendRequests = Friend::where('friend_id', Auth::id())->where('status', 'pending')->with('user')->get();
        return response()->json($friendRequests);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/requests",
     *     tags={"Friends"},
     *     summary="Send a friend request",
     *     description="Sends a friend request to another user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request sent successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Friend")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     )
     * )
     */
    public function sendFriendRequest(SendFriendRequest $request)
    {
        $friendId = $request->validated()['friend_id'];

        if (Auth::id() == $friendId) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 400);
        }

        $friendship = Friend::firstOrCreate(
            ['user_id' => Auth::id(), 'friend_id' => $friendId],
            ['status' => 'pending']
        );

        return response()->json($friendship);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/requests/{userId}",
     *     tags={"Friends"},
     *     summary="Respond to a friend request",
     *     description="Accept or decline a friend request from another user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"accepted", "declined"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request responded to successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Friend request not found"
     *     )
     * )
     */
    public function respondToFriendRequest(RespondToFriendRequest $request, $userId)
    {
        $status = $request->validated()['status'];

        $friendship = Friend::where('user_id', $userId)->where('friend_id', Auth::id())->where('status', 'pending')->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friend request not found.'], 404);
        }

        if ($status == 'accepted') {
            $friendship->update(['status' => 'accepted']);
            // Create the inverse relationship
            Friend::firstOrCreate(
                ['user_id' => Auth::id(), 'friend_id' => $userId],
                ['status' => 'accepted']
            );
        } else {
            $friendship->delete();
        }

        return response()->json(['message' => 'Friend request ' . $status . '.']);
    }

    /**
     * @OA\Delete(
     *     path="/api/friends/{friendId}",
     *     tags={"Friends"},
     *     summary="Remove a friend",
     *     description="Removes a friend from the authenticated user's friend list.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="friendId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend removed successfully"
     *     )
     * )
     */
    public function removeFriend($friendId)
    {
        Friend::where('user_id', Auth::id())->where('friend_id', $friendId)->delete();
        Friend::where('user_id', $friendId)->where('friend_id', Auth::id())->delete();

        return response()->json(['message' => 'Friend removed.']);
    }

    /**
     * @OA\Get(
     *     path="/api/friends/search",
     *     tags={"Friends"},
     *     summary="Search for users",
     *     description="Search for users by name or email.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     )
     * )
     */
    public function search(Request $request)
    {
        $searchTerm = $request->q;
        $users = User::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%")
            ->where('id', '!=', Auth::id())
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
