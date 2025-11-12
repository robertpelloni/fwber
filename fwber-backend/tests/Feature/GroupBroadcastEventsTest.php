<?php

namespace Tests\Feature;

use App\Events\GroupRoleChanged;
use App\Events\GroupOwnershipTransferred;
use App\Events\GroupMemberBanned;
use App\Events\GroupMemberUnbanned;
use App\Events\GroupMemberMuted;
use App\Events\GroupMemberUnmuted;
use App\Events\GroupMemberKicked;
use App\Models\ApiToken;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupBroadcastEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private User $admin;
    private string $ownerToken;
    private string $memberToken;
    private string $adminToken;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->owner->id,
            'display_name' => 'Owner',
            'date_of_birth' => now()->subYears(30),
            'gender' => 'male',
        ]);

        $this->member = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->member->id,
            'display_name' => 'Member',
            'date_of_birth' => now()->subYears(28),
            'gender' => 'female',
        ]);

        $this->admin = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->admin->id,
            'display_name' => 'Admin',
            'date_of_birth' => now()->subYears(29),
            'gender' => 'other',
        ]);

        $this->ownerToken = ApiToken::generateForUser($this->owner, 'test');
        $this->memberToken = ApiToken::generateForUser($this->member, 'test');
        $this->adminToken = ApiToken::generateForUser($this->admin, 'test');

        $this->group = Group::create([
            'name' => 'Broadcast Group',
            'creator_id' => $this->owner->id,
            'visibility' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'is_active' => true,
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function role_change_event_is_dispatched(): void
    {
        Event::fake();

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'moderator'
            ])
            ->assertOk();

        Event::assertDispatched(GroupRoleChanged::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id && $e->newRole === 'moderator';
        });
    }

    #[Test]
    public function ownership_transfer_event_is_dispatched(): void
    {
        Event::fake();

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/ownership/transfer", [
                'new_owner_user_id' => $this->member->id,
            ])
            ->assertOk();

        Event::assertDispatched(GroupOwnershipTransferred::class, function ($e) {
            return $e->groupId === $this->group->id && $e->toUserId === $this->member->id && $e->fromUserId === $this->owner->id;
        });
    }

    #[Test]
    public function ban_and_unban_events_are_dispatched(): void
    {
        Event::fake();

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/ban")
            ->assertOk();

        Event::assertDispatched(GroupMemberBanned::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id && $e->actorUserId === $this->owner->id;
        });

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unban")
            ->assertOk();

        Event::assertDispatched(GroupMemberUnbanned::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id;
        });
    }

    #[Test]
    public function mute_and_unmute_events_are_dispatched(): void
    {
        Event::fake();

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 30,
                'reason' => 'spam'
            ])
            ->assertOk();

        Event::assertDispatched(GroupMemberMuted::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id && $e->reason === 'spam';
        });

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unmute")
            ->assertOk();

        Event::assertDispatched(GroupMemberUnmuted::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id;
        });
    }

    #[Test]
    public function kick_event_is_dispatched(): void
    {
        Event::fake();

        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/kick")
            ->assertOk();

        Event::assertDispatched(GroupMemberKicked::class, function ($e) {
            return $e->groupId === $this->group->id && $e->targetUserId === $this->member->id && $e->actorUserId === $this->admin->id;
        });
    }

    #[Test]
    public function no_op_actions_do_not_dispatch_events(): void
    {
        Event::fake();

        // Set role to current role (member)
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'member'
            ])
            ->assertOk();
        Event::assertNotDispatched(GroupRoleChanged::class);

        // Unban when not banned
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unban")
            ->assertOk();
        Event::assertNotDispatched(GroupMemberUnbanned::class);

        // Unmute when not muted
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unmute")
            ->assertOk();
        Event::assertNotDispatched(GroupMemberUnmuted::class);

        // Mute no-op: request a shorter or equal duration than existing
        // First apply a 60 minute mute
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 60,
                'reason' => 'test'
            ])
            ->assertOk();

        Event::fake(); // reset

        // Now request a 30 minute mute (shorter) with same reason => no-op
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 30,
                'reason' => 'test'
            ])
            ->assertOk();
        Event::assertNotDispatched(GroupMemberMuted::class);
    }
}
