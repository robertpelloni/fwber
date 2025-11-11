<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupModerationTest extends TestCase
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
            'name' => 'Moderation Group',
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
    public function owner_can_mute_member_and_block_messages(): void
    {
        // Mute member for 30 minutes
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 30,
                'reason' => 'Spam',
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'is_muted' => true,
        ]);

        // Muted user cannot send message
        $this->withHeader('Authorization', "Bearer {$this->memberToken}")
            ->postJson("/api/groups/{$this->group->id}/messages", [
                'content' => 'Hello while muted'
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_mute_and_unmute_member(): void
    {
        // Admin mutes member
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'is_muted' => true,
        ]);

        // Unmute
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unmute")
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'is_muted' => false,
        ]);
    }

    #[Test]
    public function non_admin_cannot_mute(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->memberToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->admin->id}/mute", [
                'duration_minutes' => 10,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function muted_user_can_send_message_after_unmute(): void
    {
        // Mute then unmute
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 10,
            ])
            ->assertOk();
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/unmute")
            ->assertOk();

        // Now can send message
        $this->withHeader('Authorization', "Bearer {$this->memberToken}")
            ->postJson("/api/groups/{$this->group->id}/messages", [
                'content' => 'Hello after unmute'
            ])
            ->assertStatus(201);
    }

    #[Test]
    public function stats_endpoint_returns_counts(): void
    {
        // Mute member
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/members/{$this->member->id}/mute", [
                'duration_minutes' => 15,
            ])
            ->assertOk();

        // Send a message as owner
        $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->postJson("/api/groups/{$this->group->id}/messages", [
                'content' => 'Owner message'
            ])
            ->assertStatus(201);

        $response = $this->withHeader('Authorization', "Bearer {$this->ownerToken}")
            ->getJson("/api/groups/{$this->group->id}/stats")
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('total_messages', $response);
        $this->assertEquals(1, $response['total_messages']);
        $this->assertEquals(1, $response['muted_members']);
    }
}
