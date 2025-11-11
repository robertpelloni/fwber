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

        // If user was banned previously, prevent joining
        $existing = $group->members()->where('user_id', $userId)->orderByDesc('id')->first();
        if ($existing && ($existing->is_banned ?? false)) {
            return response()->json(['error' => 'You are banned from this group'], 403);
        }

        // If already an active member, block duplicate join
        if ($group->hasMember($userId)) {
            return response()->json(['error' => 'Already a member'], 400);
        }

        if ($group->isFull()) {
            return response()->json(['error' => 'Group is full'], 400);
        }

        // If there is a historical membership record (e.g., left, kicked, or previously banned but now unbanned), reactivate it
        if ($existing) {
            $existing->is_active = true;
            $existing->is_banned = false; // ensure cleared
            $existing->left_at = null;
            $existing->joined_at = now();
            // Preserve prior role if present; default to member if missing
            if (empty($existing->role)) {
                $existing->role = 'member';
            }
            $existing->save();
        } else {
            // No prior record exists; create fresh membership
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'role' => 'member',
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

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
     * Set member role (owner can set any non-owner; admin can set moderator/member)
     */
    public function setRole(Request $request, int $groupId, int $memberUserId): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,moderator,member',
        ]);

        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();

        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found or inactive'], 404);
        }

        if ($target->isOwner()) {
            return response()->json(['error' => 'Cannot change owner role'], 400);
        }

        // Permissions: owner can set any role; admin can set moderator/member only
        if ($actor->isOwner()) {
            $target->role = $validated['role'];
            $target->role_changed_at = now();
            $target->save();
            return response()->json(['message' => 'Role updated']);
        }

        if ($actor->role === 'admin') {
            if (in_array($validated['role'], ['moderator', 'member'], true)) {
                $target->role = $validated['role'];
                $target->role_changed_at = now();
                $target->save();
                return response()->json(['message' => 'Role updated']);
            }
            return response()->json(['error' => 'Admins cannot assign admin role'], 403);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    /**
     * Transfer ownership to another active member
     */
    public function transferOwnership(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'new_owner_user_id' => 'required|integer',
        ]);

        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isOwner()) {
            return response()->json(['error' => 'Only owner can transfer ownership'], 403);
        }

        $target = $group->activeMembers()->where('user_id', $validated['new_owner_user_id'])->first();
        if (!$target) {
            return response()->json(['error' => 'Target user is not an active member'], 400);
        }

        if ($target->isOwner()) {
            return response()->json(['message' => 'Already owner']);
        }

        // Perform transfer: target -> owner, actor (current owner) -> admin
        $target->role = 'owner';
        $target->role_changed_at = now();
        $target->save();

        $actor->role = 'admin';
        $actor->role_changed_at = now();
        $actor->save();

        return response()->json(['message' => 'Ownership transferred']);
    }

    /**
     * Ban a member (owner/admin). Banned members are deactivated and cannot rejoin until unbanned.
     */
    public function banMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found'], 404);
        }
        if ($target->isOwner()) {
            return response()->json(['error' => 'Cannot ban owner'], 400);
        }

        $reason = request()->input('reason');
        $target->is_banned = true;
        $target->is_active = false;
        $target->left_at = now();
        $target->banned_reason = $reason;
        $target->banned_at = now();
        $target->banned_by_user_id = $actorId;
        $target->save();

        return response()->json(['message' => 'Member banned']);
    }

    /**
     * Unban a member (owner/admin)
     */
    public function unbanMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        $target->is_banned = false;
        $target->banned_reason = null;
        $target->banned_at = null;
        $target->banned_by_user_id = null;
        $target->save();

        return response()->json(['message' => 'Member unbanned']);
    }

    /**
     * Mute a member temporarily (owner/admin). Accepts either duration_minutes or until timestamp.
     */
    public function muteMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found or inactive'], 404);
        }
        if ($target->isOwner()) {
            return response()->json(['error' => 'Cannot mute owner'], 400);
        }

        $validated = request()->validate([
            'duration_minutes' => 'nullable|integer|min:1|max:10080', // up to 7 days
            'until' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $until = null;
        if (!empty($validated['until'])) {
            $until = \Carbon\Carbon::parse($validated['until']);
        } elseif (!empty($validated['duration_minutes'])) {
            $until = now()->addMinutes($validated['duration_minutes']);
        }

        if (!$until) {
            $until = now()->addHour(); // default 1 hour
        }

        $target->is_muted = true;
        $target->muted_until = $until;
        $target->mute_reason = $validated['reason'] ?? null;
        $target->muted_by_user_id = $actorId;
        $target->save();

        return response()->json(['message' => 'Member muted', 'muted_until' => $until->toIso8601String()]);
    }

    /**
     * Unmute a member.
     */
    public function unmuteMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        $target->is_muted = false;
        $target->muted_until = null;
        $target->mute_reason = null;
        $target->muted_by_user_id = null;
        $target->save();

        return response()->json(['message' => 'Member unmuted']);
    }

    /**
     * Simple analytics endpoint for group (owner/admin only).
     */
    public function stats(int $groupId): JsonResponse
    {
        $group = Group::with(['activeMembers'])->findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roleCounts = $group->activeMembers()
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        $totalMessages = \App\Models\GroupMessage::where('group_id', $groupId)->count();
        $bannedCount = $group->members()->where('is_banned', true)->count();
        $mutedCount = $group->activeMembers()->where('is_muted', true)->count();

        return response()->json([
            'group_id' => $groupId,
            'roles' => $roleCounts,
            'total_messages' => $totalMessages,
            'banned_members' => $bannedCount,
            'muted_members' => $mutedCount,
            'max_members' => $group->max_members,
            'is_full' => $group->isFull(),
        ]);
    }

    /**
     * Kick a member (owner/admin). Makes member inactive but not banned.
     */
    public function kickMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            return response()->json(['error' => 'Member not found or inactive'], 404);
        }
        if ($target->isOwner()) {
            return response()->json(['error' => 'Cannot kick owner'], 400);
        }

        $target->is_active = false;
        $target->left_at = now();
        $target->save();

        return response()->json(['message' => 'Member removed']);
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
