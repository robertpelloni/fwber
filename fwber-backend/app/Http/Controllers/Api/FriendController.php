<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RespondToFriendRequest;
use App\Http\Requests\Api\SendFriendRequest;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

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
        $user = Auth::user();
        $friends = Friend::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('friend_id', $user->id);
        })->where('status', 'accepted')
            ->with(['user', 'friend'])
            ->get();

        $friendDetails = $friends->map(function ($friend) use ($user) {
            return $friend->user_id == $user->id ? $friend->friend : $friend->user;
        })->unique('id')->values();

        return response()->json($friendDetails);
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
        $user = Auth::user();
        $requests = Friend::where('friend_id', $user->id)->where('status', 'pending')->with('user')->get();

        return response()->json($requests);
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
     *         response=201,
     *         description="Friend request sent successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Friend")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Friend request already sent or you are already friends"
     *     )
     * )
     */
    public function sendFriendRequest(SendFriendRequest $request)
    {
        $user = Auth::user();
        $friendId = $request->validated()['friend_id'];

        if ($user->id == $friendId) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 400);
        }

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
            'status' => 'pending'
        ]);

        return response()->json($friendRequest, 201);
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
        $user = Auth::user();
        $friendRequest = Friend::where('user_id', $userId)->where('friend_id', $user->id)->where('status', 'pending')->first();

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found.'], 404);
        }

        $status = $request->validated()['status'];

        if ($status == 'accepted') {
            $friendRequest->update(['status' => 'accepted']);
            Friend::firstOrCreate(
                ['user_id' => $user->id, 'friend_id' => $userId],
                ['status' => 'accepted']
            );
            return response()->json($friendRequest);
        } else {
            $friendRequest->delete();
            return response()->json(['message' => 'Friend request declined.']);
        }
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
        $user = Auth::user();
        $friendship = Friend::where(function ($query) use ($user, $friendId) {
            $query->where('user_id', $user->id)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($user, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $user->id);
        })->where('status', 'accepted')->first();

        if ($friendship) {
            $friendship->delete();
        }
        // Also try to delete any pending requests just in case
        Friend::where('user_id', $user->id)->where('friend_id', $friendId)->delete();
        Friend::where('user_id', $friendId)->where('friend_id', $user->id)->delete();

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
     *         name="query",
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
        $query = $request->input('query') ?? $request->input('q');
        
        if (!$query || strlen($query) < 2) {
             return response()->json(['message' => 'The query field is required and must be at least 2 characters.'], 422);
        }

        $user = Auth::user();

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', $user->id)
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
