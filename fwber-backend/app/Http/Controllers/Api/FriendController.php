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
     *     path="/friends",
     *     summary="Get the user's friends",
     *     tags={"Friends"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getFriends()
    {
        $friends = Auth::user()->friends;
        return response()->json($friends);
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
     *     path="/friends/requests",
     *     summary="Get the user's friend requests",
     *     tags={"Friends"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getFriendRequests()
    {
        $friendRequests = Friend::where('friend_id', Auth::id())->where('status', 'pending')->with('user')->get();
        return response()->json($friendRequests);
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
     *     path="/api/friends/requests/{userId}",
     *     tags={"Friends"},
     *     summary="Respond to a friend request",
     *     description="Accept or decline a friend request from another user.",
     *     security={{"bearerAuth":{}}},
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
     *     path="/api/friends/{friendId}",
     *     tags={"Friends"},
     *     summary="Remove a friend",
     *     description="Removes a friend from the authenticated user's friend list.",
     *     security={{"bearerAuth":{}}},
     *     path="/friends/{friendId}",
     *     summary="Remove a friend",
     *     tags={"Friends"},
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
     *     @OA\Response(response=200, description="Friend removed"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function removeFriend($friendId)
    {
        Friend::where('user_id', Auth::id())->where('friend_id', $friendId)->delete();
        Friend::where('user_id', $friendId)->where('friend_id', Auth::id())->delete();
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
     *     path="/api/friends/search",
     *     tags={"Friends"},
     *     summary="Search for users",
     *     description="Search for users by name or email.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *     path="/friends/search",
     *     summary="Search for users",
     *     tags={"Friends"},
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
     *     @OA\Response(response=200, description="Successful operation"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function search(Request $request)
    {
        $searchTerm = $request->q;
        $users = User::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%")
            ->where('id', '!=', Auth::id())
            ->limit(10)
        $request->validate(['query' => 'required|string|min:2']);

        $user = Auth::user();
        $query = $request->query('query');

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', $user->id)
            ->get();

        return response()->json($users);
    }
}
