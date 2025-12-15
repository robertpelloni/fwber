<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_groups()
    {
        $user = User::factory()->create();
        Group::factory()->count(3)->create(['privacy' => 'public']);

        $response = $this->actingAs($user)
            ->getJson('/api/groups');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'groups');
    }

    public function test_can_create_group()
    {
        $user = User::factory()->create();

        $groupData = [
            'name' => 'Test Group',
            'description' => 'This is a test group',
            'privacy' => 'public',
            'icon' => 'test-icon.png',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/groups', $groupData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Group']);

        $this->assertDatabaseHas('groups', ['name' => 'Test Group']);
        $this->assertDatabaseHas('group_members', [
            'user_id' => $user->id,
            'role' => 'owner'
        ]);
    }

    public function test_can_join_group()
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();
        $group = Group::factory()->create(['created_by_user_id' => $creator->id]);

        $response = $this->actingAs($joiner)
            ->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
            'role' => 'member'
        ]);
    }

    public function test_can_leave_group()
    {
        $creator = User::factory()->create();
        $leaver = User::factory()->create();
        $group = Group::factory()->create(['created_by_user_id' => $creator->id]);
        
        // Manually add member
        \App\Models\GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $leaver->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($leaver)
            ->postJson("/api/groups/{$group->id}/leave");

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $leaver->id,
            'is_active' => false,
        ]);
    }
}
