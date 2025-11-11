<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\GroupMessageRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * List user's groups
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        
        $groups = Group::whereHas('activeMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->active()
        ->with(['creator', 'activeMembers'])
        ->withCount('activeMembers')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json(['groups' => $groups]);
    }

    /**
     * Create a new group
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'sometimes|in:public,private',
            'max_members' => 'sometimes|integer|min:2|max:500',
        ]);

        $userId = Auth::id();

        DB::beginTransaction();
        try {
            $group = Group::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'visibility' => $validated['visibility'] ?? 'public',
                'creator_id' => $userId,
                'max_members' => $validated['max_members'] ?? 100,
                'is_active' => true,
            ]);

            // Add creator as owner
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            DB::commit();

            return response()->json(['group' => $group->load('creator', 'activeMembers')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create group'], 500);
        }
    }

    /**
     * Get group details
     */
    public function show(int $groupId): JsonResponse
    {
        $group = Group::with(['creator', 'activeMembers.user'])
            ->withCount('activeMembers')
            ->findOrFail($groupId);

        $userId = Auth::id();

        // Check visibility
        if ($group->visibility === 'private' && !$group->hasMember($userId)) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        return response()->json([
            'group' => $group,
            'is_member' => $group->hasMember($userId),
            'user_role' => $group->getMemberRole($userId),
        ]);
    }

    /**
     * Update group
     */
    public function update(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'sometimes|in:public,private',
            'max_members' => 'sometimes|integer|min:2|max:500',
        ]);

        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        $member = $group->activeMembers()->where('user_id', $userId)->first();
        
        if (!$member || !$member->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $group->update($validated);

        return response()->json(['group' => $group->load('creator', 'activeMembers')]);
    }

    /**
     * Delete/deactivate group
     */
    public function destroy(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        $member = $group->activeMembers()->where('user_id', $userId)->first();
        
        if (!$member || !$member->isOwner()) {
            return response()->json(['error' => 'Only owner can delete group'], 403);
        }

        $group->is_active = false;
        $group->save();

        return response()->json(['message' => 'Group deactivated']);
    }

    /**
     * Join a group
     */
    public function join(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        if ($group->visibility === 'private') {
            return response()->json(['error' => 'Cannot join private group'], 403);
        }

        if ($group->hasMember($userId)) {
            return response()->json(['error' => 'Already a member'], 400);
        }

        if ($group->isFull()) {
            return response()->json(['error' => 'Group is full'], 400);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Joined group successfully']);
    }

    /**
     * Leave a group
     */
    public function leave(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        $member = $group->activeMembers()->where('user_id', $userId)->first();

        if (!$member) {
            return response()->json(['error' => 'Not a member'], 400);
        }

        if ($member->isOwner()) {
            return response()->json(['error' => 'Owner cannot leave. Transfer ownership or delete group.'], 400);
        }

        $member->is_active = false;
        $member->left_at = now();
        $member->save();

        return response()->json(['message' => 'Left group successfully']);
    }

    /**
     * Discover public groups
     */
    public function discover(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Group::public()
            ->active()
            ->with(['creator'])
            ->withCount('activeMembers');

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($groups);
    }
}
