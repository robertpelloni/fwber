<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FriendController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $friendships = Friend::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('friend_id', $user->id);
            })
            ->with(['user.profile', 'friend.profile'])
            ->latest()
            ->get();

        $friends = $friendships->map(function (Friend $friendship) use ($user) {
            return $friendship->user_id === $user->id ? $friendship->friend : $friendship->user;
        })->unique('id')->values();

        return response()->json($friends);
    }

    public function requests(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = Friend::query()
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->with('user.profile')
            ->latest()
            ->get();

        return response()->json($requests);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'friend_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $friendId = (int) $validated['friend_id'];
        if ($friendId === $user->id) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 400);
        }

        $existing = Friend::query()
            ->where(function ($query) use ($user, $friendId): void {
                $query->where('user_id', $user->id)->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId): void {
                $query->where('user_id', $friendId)->where('friend_id', $user->id);
            })
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Friend request already exists or you are already connected.'], 409);
        }

        $friendRequest = Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        return response()->json($friendRequest->load('friend.profile'), 201);
    }

    public function respond(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined'])],
        ]);

        $friendRequest = Friend::query()
            ->where('user_id', $userId)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $friendRequest) {
            return response()->json(['message' => 'Friend request not found.'], 404);
        }

        if ($validated['status'] === 'accepted') {
            $friendRequest->update(['status' => 'accepted']);

            Friend::firstOrCreate(
                ['user_id' => $user->id, 'friend_id' => $userId],
                ['status' => 'accepted']
            );

            return response()->json($friendRequest->fresh(['user.profile', 'friend.profile']));
        }

        $friendRequest->update(['status' => 'declined']);

        return response()->json(['message' => 'Friend request declined.']);
    }

    public function destroy(Request $request, int $friendId): JsonResponse
    {
        $user = $request->user();

        Friend::query()
            ->where(function ($query) use ($user, $friendId): void {
                $query->where('user_id', $user->id)->where('friend_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId): void {
                $query->where('user_id', $friendId)->where('friend_id', $user->id);
            })
            ->delete();

        return response()->json(['message' => 'Friend removed successfully.']);
    }

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $results = User::query()
            ->with('profile')
            ->where('id', '!=', $user->id)
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(12)
            ->get();

        return response()->json($results);
    }
}
