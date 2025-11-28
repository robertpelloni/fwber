<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupModerationEvent;
use App\Events\GroupRoleChanged;
use App\Events\GroupOwnershipTransferred;
use App\Events\GroupMemberBanned;
use App\Events\GroupMemberUnbanned;
use App\Events\GroupMemberMuted;
use App\Events\GroupMemberUnmuted;
use App\Events\GroupMemberKicked;
use Illuminate\Support\Facades\DB;

class GroupService
{
    public function createGroup(int $userId, array $data): Group
    {
        return DB::transaction(function () use ($userId, $data) {
            $group = Group::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
                'creator_id' => $userId,
                'max_members' => $data['max_members'] ?? 100,
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

            return $group;
        });
    }

    public function joinGroup(Group $group, int $userId): void
    {
        if ($group->visibility === 'private') {
            throw new \Exception('Cannot join private group', 403);
        }

        // If user was banned previously, prevent joining
        $existing = $group->members()->where('user_id', $userId)->orderByDesc('id')->first();
        if ($existing && ($existing->is_banned ?? false)) {
            throw new \Exception('You are banned from this group', 403);
        }

        // If already an active member, block duplicate join
        if ($group->hasMember($userId)) {
            throw new \Exception('Already a member', 400);
        }

        if ($group->isFull()) {
            throw new \Exception('Group is full', 400);
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
    }

    public function leaveGroup(Group $group, int $userId): void
    {
        $member = $group->activeMembers()->where('user_id', $userId)->first();

        if (!$member) {
            throw new \Exception('Not a member', 400);
        }

        if ($member->isOwner()) {
            throw new \Exception('Owner cannot leave. Transfer ownership or delete group.', 400);
        }

        $member->is_active = false;
        $member->left_at = now();
        $member->save();
    }

    public function setMemberRole(Group $group, int $actorId, int $memberUserId, string $newRole): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            throw new \Exception('Member not found or inactive', 404);
        }

        if ($target->isOwner()) {
            throw new \Exception('Cannot change owner role', 400);
        }

        // Permissions: owner can set any role; admin can set moderator/member only
        if ($actor->isOwner()) {
            if ($target->role === $newRole) {
                return;
            }
            $this->updateRole($group, $actorId, $target, $newRole);
            return;
        }

        if ($actor->role === 'admin') {
            if (in_array($newRole, ['moderator', 'member'], true)) {
                if ($target->role === $newRole) {
                    return;
                }
                $this->updateRole($group, $actorId, $target, $newRole);
                return;
            }
            throw new \Exception('Admins cannot assign admin role', 403);
        }

        throw new \Exception('Unauthorized', 403);
    }

    private function updateRole(Group $group, int $actorId, GroupMember $target, string $newRole): void
    {
        $target->role = $newRole;
        $target->role_changed_at = now();
        $target->save();
        
        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $target->user_id,
            'action' => 'role_change',
            'reason' => null,
            'metadata' => ['new_role' => $newRole],
            'occurred_at' => now(),
        ]);
        
        event(new GroupRoleChanged($group->id, $actorId, $target->user_id, $newRole));
    }

    public function transferOwnership(Group $group, int $actorId, int $newOwnerUserId): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isOwner()) {
            throw new \Exception('Only owner can transfer ownership', 403);
        }

        $target = $group->activeMembers()->where('user_id', $newOwnerUserId)->first();
        if (!$target) {
            throw new \Exception('Target user is not an active member', 400);
        }

        if ($target->isOwner()) {
            return;
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
    }

    public function banMember(Group $group, int $actorId, int $memberUserId, ?string $reason): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            throw new \Exception('Member not found', 404);
        }
        if ($target->isOwner()) {
            throw new \Exception('Cannot ban owner', 400);
        }

        if ($target->is_banned) {
            return;
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
    }

    public function unbanMember(Group $group, int $actorId, int $memberUserId): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            throw new \Exception('Member not found', 404);
        }

        if (!$target->is_banned) {
            return;
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
    }

    public function muteMember(Group $group, int $actorId, int $memberUserId, ?int $durationMinutes, ?string $untilStr, ?string $reason): string
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            throw new \Exception('Member not found or inactive', 404);
        }
        if ($target->isOwner()) {
            throw new \Exception('Cannot mute owner', 400);
        }

        $until = null;
        if (!empty($untilStr)) {
            $until = \Carbon\Carbon::parse($untilStr);
        } elseif (!empty($durationMinutes)) {
            $until = now()->addMinutes($durationMinutes);
        }

        if (!$until) {
            $until = now()->addHour(); // default 1 hour
        }

        // No-op suppression
        if ($target->is_muted && $target->muted_until && $until && $target->muted_until >= $until && (($target->mute_reason ?? null) === ($reason ?? null))) {
            return $target->muted_until->toIso8601String();
        }
        
        $target->is_muted = true;
        $target->muted_until = $until;
        $target->mute_reason = $reason ?? null;
        $target->muted_by_user_id = $actorId;
        $target->save();

        GroupModerationEvent::create([
            'group_id' => $group->id,
            'actor_user_id' => $actorId,
            'target_user_id' => $memberUserId,
            'action' => 'mute',
            'reason' => $reason ?? null,
            'metadata' => ['muted_until' => $until->toIso8601String()],
            'occurred_at' => now(),
        ]);
        
        event(new GroupMemberMuted($group->id, $actorId, $memberUserId, $until->toIso8601String(), $reason ?? null));
        
        return $until->toIso8601String();
    }

    public function unmuteMember(Group $group, int $actorId, int $memberUserId): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->members()->where('user_id', $memberUserId)->orderByDesc('id')->first();
        if (!$target) {
            throw new \Exception('Member not found', 404);
        }

        if (!$target->is_muted) {
            return;
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
    }

    public function kickMember(Group $group, int $actorId, int $memberUserId): void
    {
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            throw new \Exception('Unauthorized', 403);
        }

        $target = $group->activeMembers()->where('user_id', $memberUserId)->first();
        if (!$target) {
            throw new \Exception('Member not found or inactive', 404);
        }
        if ($target->isOwner()) {
            throw new \Exception('Cannot kick owner', 400);
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
    }
}
