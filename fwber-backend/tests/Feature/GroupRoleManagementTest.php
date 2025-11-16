<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\UserProfile;
use Tests\Traits\RefreshDatabaseSilently;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupRoleManagementTest extends TestCase
{
    use RefreshDatabaseSilently;

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
            'name' => 'Role Group',
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
    public function owner_can_transfer_ownership(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/ownership/transfer", [
                'new_owner_user_id' => $this->member->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function non_owner_cannot_transfer_ownership(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/ownership/transfer", [
                'new_owner_user_id' => $this->member->id,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function owner_can_set_role_any(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'admin',
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function admin_cannot_assign_admin_role(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'admin',
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_assign_moderator(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'moderator',
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'role' => 'moderator',
        ]);
    }

    #[Test]
    public function owner_can_ban_and_unban_member(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/ban")
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'is_banned' => true,
            'is_active' => false,
        ]);

        // Banned user cannot rejoin
        $this->withHeader('Authorization', "Bearer {$this->memberToken}")
            ->postJson("/api/groups/{$this->group->id}/join")
            ->assertStatus(403);

        // Unban
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unban")
            ->assertOk();

        // Now can rejoin
        $this->withHeader('Authorization', "Bearer {$this->memberToken}")
            ->postJson("/api/groups/{$this->group->id}/join")
            ->assertOk();
    }

    #[Test]
    public function admin_can_kick_member_but_not_owner(): void
    {
        // Kick member
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/kick")
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'is_active' => false,
        ]);

        // Cannot kick owner
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->owner->id}/kick")
            ->assertStatus(400);
    }
}
