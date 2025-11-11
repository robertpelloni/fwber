<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupModerationEvent;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupModerationAuditLogTest extends TestCase
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
            'name' => 'Audit Group',
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
    public function ban_and_unban_are_logged(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/ban", [
                'reason' => 'TOS'
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_moderation_events', [
            'group_id' => $this->group->id,
            'actor_user_id' => $this->owner->id,
            'target_user_id' => $this->member->id,
            'action' => 'ban',
            'reason' => 'TOS',
        ]);

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unban")
            ->assertOk();

        $this->assertDatabaseHas('group_moderation_events', [
            'group_id' => $this->group->id,
            'actor_user_id' => $this->owner->id,
            'target_user_id' => $this->member->id,
            'action' => 'unban',
        ]);
    }

    #[Test]
    public function role_change_and_ownership_transfer_are_logged(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/role", [
                'role' => 'moderator'
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_moderation_events', [
            'group_id' => $this->group->id,
            'actor_user_id' => $this->owner->id,
            'target_user_id' => $this->member->id,
            'action' => 'role_change',
        ]);

        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/ownership/transfer", [
                'new_owner_user_id' => $this->member->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_moderation_events', [
            'group_id' => $this->group->id,
            'actor_user_id' => $this->owner->id,
            'target_user_id' => $this->member->id,
            'action' => 'ownership_transfer',
        ]);
    }
}
