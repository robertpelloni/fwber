<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\GroupMessageRead;
use App\Models\GroupModerationEvent;
use App\Events\GroupRoleChanged;
use App\Events\GroupOwnershipTransferred;
use App\Events\GroupMemberBanned;
use App\Events\GroupMemberUnbanned;
use App\Events\GroupMemberMuted;
use App\Events\GroupMemberUnmuted;
use App\Events\GroupMemberKicked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * List user's groups
    *
    * @OA\Get(
    *   path="/groups",
    *   tags={"Groups"},
    *   summary="List groups the authenticated user belongs to",
    *   security={{"bearerAuth":{}}},
    *   @OA\Response(
    *     response=200,
    *     description="List of groups",
    *     @OA\JsonContent(
    *       type="object",
    *       @OA\Property(property="groups", type="array", @OA\Items(ref="#/components/schemas/Group"))
    *     )
    *   ),
    *   @OA\Response(response=401, ref="#/components/responses/Unauthorized")
    * )
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
        *
        * @OA\Post(
        *   path="/groups",
        *   tags={"Groups"},
        *   summary="Create a new group",
        *   security={{"bearerAuth":{}}},
        *   @OA\RequestBody(
        *     required=true,
        *     @OA\JsonContent(
        *       required={"name"},
        *       @OA\Property(property="name", type="string", maxLength=100),
        *       @OA\Property(property="description", type="string", nullable=true, maxLength=1000),
        *       @OA\Property(property="visibility", type="string", enum={"public","private"}),
        *       @OA\Property(property="max_members", type="integer", minimum=2, maximum=500)
        *     )
        *   ),
        *   @OA\Response(
        *     response=201,
        *     description="Group created",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", ref="#/components/schemas/Group")
        *     )
        *   ),
        *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Get(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Get group details",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Group details",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", type="object"),
        *       @OA\Property(property="is_member", type="boolean"),
        *       @OA\Property(property="user_role", type="string", nullable=true)
        *     )
        *   ),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Put(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Update group settings",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(@OA\JsonContent(
        *     @OA\Property(property="name", type="string"),
        *     @OA\Property(property="description", type="string", nullable=true),
        *     @OA\Property(property="visibility", type="string", enum={"public","private"}),
        *     @OA\Property(property="max_members", type="integer")
        *   )),
        *   @OA\Response(response=200, description="Updated",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", type="object")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Delete(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Deactivate a group (owner only)",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Deactivated",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="message", type="string")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Post(
        *   path="/groups/{groupId}/join",
        *   tags={"Groups"},
        *   summary="Join a public group",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Joined"),
        *   @OA\Response(response=400, description="Already a member or full"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Post(
        *   path="/groups/{groupId}/leave",
        *   tags={"Groups"},
        *   summary="Leave a group",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Left group"),
        *   @OA\Response(response=400, description="Not a member or owner cannot leave"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/role",
        *   tags={"Groups"},
        *   summary="Set a member's role",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(required=true, @OA\JsonContent(
        *     required={"role"},
        *     @OA\Property(property="role", type="string", enum={"admin","moderator","member"})
        *   )),
        *   @OA\Response(response=200, description="Role updated or unchanged"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=400, description="Cannot change owner role or invalid assignment"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
            if ($target->role === $validated['role']) {
                return response()->json(['message' => 'Role unchanged']);
            }
            $target->role = $validated['role'];
            $target->role_changed_at = now();
            $target->save();
            GroupModerationEvent::create([
                'group_id' => $group->id,
                'actor_user_id' => $actorId,
                'target_user_id' => $memberUserId,
                'action' => 'role_change',
                'reason' => null,
                'metadata' => ['new_role' => $validated['role']],
                'occurred_at' => now(),
            ]);
            event(new GroupRoleChanged($group->id, $actorId, $memberUserId, $validated['role']));
            return response()->json(['message' => 'Role updated']);
        }

        if ($actor->role === 'admin') {
            if (in_array($validated['role'], ['moderator', 'member'], true)) {
                if ($target->role === $validated['role']) {
                    return response()->json(['message' => 'Role unchanged']);
                }
                $target->role = $validated['role'];
                $target->role_changed_at = now();
                $target->save();
                GroupModerationEvent::create([
                    'group_id' => $group->id,
                    'actor_user_id' => $actorId,
                    'target_user_id' => $memberUserId,
                    'action' => 'role_change',
                    'reason' => null,
                    'metadata' => ['new_role' => $validated['role']],
                    'occurred_at' => now(),
                ]);
                event(new GroupRoleChanged($group->id, $actorId, $memberUserId, $validated['role']));
                return response()->json(['message' => 'Role updated']);
            }
            return response()->json(['error' => 'Admins cannot assign admin role'], 403);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    /**
     * Transfer ownership to another active member
        *
        * @OA\Post(
        *   path="/groups/{groupId}/ownership/transfer",
        *   tags={"Groups"},
        *   summary="Transfer group ownership",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(required=true, @OA\JsonContent(
        *     required={"new_owner_user_id"},
        *     @OA\Property(property="new_owner_user_id", type="integer")
        *   )),
        *   @OA\Response(response=200, description="Ownership transferred"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=400, description="Target not active member or already owner"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $target->user_id,
            'action' => 'ownership_transfer',
            'reason' => null,
            'metadata' => ['from' => $actorId, 'to' => $target->user_id],
            'occurred_at' => now(),
        ]);
        event(new GroupOwnershipTransferred($group->id, $actorId, $target->user_id));

        return response()->json(['message' => 'Ownership transferred']);
    }

    /**
     * Ban a member (owner/admin). Banned members are deactivated and cannot rejoin until unbanned.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/ban",
        *   tags={"Groups"},
        *   summary="Ban a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member banned or already banned"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        if ($target->is_banned) {
            return response()->json(['message' => 'Already banned']);
        }
        $target->is_banned = true;
        $target->is_active = false;
        $target->left_at = now();
        $target->banned_reason = $reason;
        $target->banned_at = now();
        $target->banned_by_user_id = $actorId;
        $target->save();

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'ban',
            'reason' => $reason,
            'metadata' => null,
            'occurred_at' => now(),
        ]);
        event(new GroupMemberBanned($group->id, $actorId, $memberUserId, $reason));

        return response()->json(['message' => 'Member banned']);
    }

    /**
     * Unban a member (owner/admin)
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/unban",
        *   tags={"Groups"},
        *   summary="Unban a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member unbanned or not banned"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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

        if (!$target->is_banned) {
            return response()->json(['message' => 'Not banned']);
        }
        $target->is_banned = false;
        $target->banned_reason = null;
        $target->banned_at = null;
        $target->banned_by_user_id = null;
        $target->save();

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'unban',
            'reason' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ]);
        event(new GroupMemberUnbanned($group->id, $actorId, $memberUserId));

        return response()->json(['message' => 'Member unbanned']);
    }

    /**
     * Mute a member temporarily (owner/admin). Accepts either duration_minutes or until timestamp.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/mute",
        *   tags={"Groups"},
        *   summary="Mute a member temporarily",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(@OA\JsonContent(
        *     @OA\Property(property="duration_minutes", type="integer", minimum=1, maximum=10080),
        *     @OA\Property(property="until", type="string", format="date-time"),
        *     @OA\Property(property="reason", type="string", maxLength=255)
        *   )),
        *   @OA\Response(response=200, description="Muted or unchanged"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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

        // No-op suppression: if already muted until a later time or equal and same reason
        if ($target->is_muted && $target->muted_until && $until && $target->muted_until >= $until && (($target->mute_reason ?? null) === ($validated['reason'] ?? null))) {
            return response()->json(['message' => 'Mute unchanged', 'muted_until' => $target->muted_until->toIso8601String()]);
        }
        $target->is_muted = true;
        $target->muted_until = $until;
        $target->mute_reason = $validated['reason'] ?? null;
        $target->muted_by_user_id = $actorId;
        $target->save();

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'mute',
            'reason' => $validated['reason'] ?? null,
            'metadata' => ['muted_until' => $until->toIso8601String()],
            'occurred_at' => now(),
        ]);
        event(new GroupMemberMuted($group->id, $actorId, $memberUserId, $until->toIso8601String(), $validated['reason'] ?? null));

        return response()->json(['message' => 'Member muted', 'muted_until' => $until->toIso8601String()]);
    }

    /**
     * Unmute a member.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/unmute",
        *   tags={"Groups"},
        *   summary="Unmute a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Unmuted or not muted"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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

        if (!$target->is_muted) {
            return response()->json(['message' => 'Not muted']);
        }
        $target->is_muted = false;
        $target->muted_until = null;
        $target->mute_reason = null;
        $target->muted_by_user_id = null;
        $target->save();

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'unmute',
            'reason' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ]);
        event(new GroupMemberUnmuted($group->id, $actorId, $memberUserId));

        return response()->json(['message' => 'Member unmuted']);
    }

    /**
     * Simple analytics endpoint for group (owner/admin only).
        *
        * @OA\Get(
        *   path="/groups/{groupId}/stats",
        *   tags={"Groups"},
        *   summary="Group statistics (admin/owner)",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Stats",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group_id", type="integer"),
        *       @OA\Property(property="roles", type="object"),
        *       @OA\Property(property="total_messages", type="integer"),
        *       @OA\Property(property="banned_members", type="integer"),
        *       @OA\Property(property="muted_members", type="integer"),
        *       @OA\Property(property="max_members", type="integer"),
        *       @OA\Property(property="is_full", type="boolean")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/kick",
        *   tags={"Groups"},
        *   summary="Kick a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member removed"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
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

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'kick',
            'reason' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ]);
        event(new GroupMemberKicked($group->id, $actorId, $memberUserId));

        return response()->json(['message' => 'Member removed']);
    }

    /**
     * Discover public groups
        *
        * @OA\Get(
        *   path="/groups/discover",
        *   tags={"Groups"},
        *   summary="Discover public groups",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
        *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50)),
        *   @OA\Response(response=200, description="Paginated groups list")
        * )
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
