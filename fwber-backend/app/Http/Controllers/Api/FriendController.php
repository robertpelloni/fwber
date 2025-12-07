<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    /**
     * @OA\Get(
     *     path="/friends",
     *     summary="Get the user's friends",
     *     tags={"Friends"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getFriends()
    {
        $user = Auth::user();
        $friends = Friend::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('friend_id', $user->id);
        })->where('status', 'accepted')->get();

        $friendDetails = $friends->map(function ($friend) use ($user) {
            $friendId = $friend->user_id == $user->id ? $friend->friend_id : $friend->user_id;
            return User::find($friendId);
        });

        return response()->json($friendDetails);
    }

    /**
     * @OA\Get(
     *     path="/friends/requests",
     *     summary="Get the user's friend requests",
     *     tags={"Friends"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getFriendRequests()
    {
        $user = Auth::user();
        $requests = Friend::where('friend_id', $user->id)->where('status', 'pending')->with('user')->get();

        return response()->json($requests);
    }

    /**
     * @OA\Post(
     *     path="/friends/requests",
     *     summary="Send a friend request",
     *     tags={"Friends"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"friend_id"},
     *             @OA\Property(property="friend_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Friend request sent"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function sendFriendRequest(Request $request)
    {
        $request->validate(['friend_id' => 'required|exists:users,id']);

        $user = Auth::user();
        $friendId = $request->friend_id;

        // Check if they are already friends or a request is pending
        $existingFriendship = Friend::where(function ($query) use ($user, $friendId) {
            $query->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($user, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $user->id);
        })->first();

        if ($existingFriendship) {
            return response()->json(['message' => 'Friend request already sent or you are already friends.'], 409);
        }

        $friendRequest = Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friendId,
        ]);

        return response()->json($friendRequest, 201);
    }

    /**
     * @OA\Post(
     *     path="/friends/requests/{userId}",
     *     summary="Respond to a friend request",
     *     tags={"Friends"},
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
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function respondToFriendRequest(Request $request, $userId)
    {
        $request->validate(['status' => 'required|in:accepted,declined']);

        $user = Auth::user();
        $friendRequest = Friend::where('user_id', $userId)->where('friend_id', $user->id)->where('status', 'pending')->firstOrFail();

        if ($request->status == 'accepted') {
            $friendRequest->update(['status' => 'accepted']);
            return response()->json($friendRequest);
        } else {
            $friendRequest->delete();
            return response()->json(['message' => 'Friend request declined.']);
        }
    }

    /**
     * @OA\Delete(
     *     path="/friends/{friendId}",
     *     summary="Remove a friend",
     *     tags={"Friends"},
     *     @OA\Parameter(
     *         name="friendId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Friend removed"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function removeFriend($friendId)
    {
        $user = Auth::user();
        $friendship = Friend::where(function ($query) use ($user, $friendId) {
            $query->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($user, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $user->id);
        })->where('status', 'accepted')->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'Friend removed.']);
    }

    /**
     * @OA\Get(
     *     path="/friends/search",
     *     summary="Search for users",
     *     tags={"Friends"},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2']);

        $user = Auth::user();
        $query = $request->query('query');

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', $user->id)
            ->get();

        return response()->json($users);
    }
}
