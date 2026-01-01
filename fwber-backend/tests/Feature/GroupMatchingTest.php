<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group_with_matching_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Hiking Group',
            'description' => 'A group for hiking',
            'privacy' => 'public',
            'category' => 'hobbies',
            'tags' => ['outdoors', 'hiking', 'nature'],
            'matching_enabled' => true,
            'location_lat' => 40.7128,
            'location_lon' => -74.0060,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('groups', [
            'name' => 'Hiking Group',
            'category' => 'hobbies',
            'matching_enabled' => true,
            'location_lat' => 40.7128,
            'location_lon' => -74.0060,
        ]);

        $group = Group::where('name', 'Hiking Group')->first();
        $this->assertEquals(['outdoors', 'hiking', 'nature'], $group->tags);
    }

    public function test_can_update_group_matching_fields()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'created_by_user_id' => $user->id,
            'creator_id' => $user->id,
        ]);
        
        // manually create member entry for creator (usually handled by service)
        $group->members()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->putJson("/api/groups/{$group->id}", [
            'category' => 'social',
            'tags' => ['friends', 'hangout'],
            'matching_enabled' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'category' => 'social',
            'matching_enabled' => false,
        ]);

        $group->refresh();
        $this->assertEquals(['friends', 'hangout'], $group->tags);
    }

    public function test_find_group_matches()
    {
        $user = User::factory()->create();
        
        // Target Group (NYC)
        $groupA = Group::factory()->create([
            'name' => 'NYC Hikers',
            'category' => 'hobbies',
            'matching_enabled' => true,
            'location_lat' => 40.7128,
            'location_lon' => -74.0060,
            'tags' => ['hiking', 'outdoors'],
            'created_by_user_id' => $user->id,
            'creator_id' => $user->id,
        ]);

        $groupA->members()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Matching Group (Nearby NYC - Brooklyn, same category)
        $groupB = Group::factory()->create([
            'name' => 'Brooklyn Walkers',
            'category' => 'hobbies',
            'matching_enabled' => true,
            'location_lat' => 40.6782,
            'location_lon' => -73.9442,
            'tags' => ['walking', 'outdoors'],
            'created_by_user_id' => User::factory()->create()->id,
        ]);

        // Non-Matching Group (Far away - LA)
        $groupC = Group::factory()->create([
            'name' => 'LA Hikers',
            'category' => 'hobbies',
            'matching_enabled' => true,
            'location_lat' => 34.0522,
            'location_lon' => -118.2437,
            'created_by_user_id' => User::factory()->create()->id,
        ]);

        // Non-Matching Group (Nearby but matching disabled)
        $groupD = Group::factory()->create([
            'name' => 'Private Club',
            'category' => 'hobbies',
            'matching_enabled' => false,
            'location_lat' => 40.7128,
            'location_lon' => -74.0060,
            'created_by_user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/groups/{$groupA->id}/matches");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches') // Only Group B should match
            ->assertJsonPath('matches.0.id', $groupB->id);
    }

    public function test_can_request_group_match()
    {
        $user = User::factory()->create();
        
        // Source Group (User is Owner)
        $groupA = Group::factory()->create([
            'name' => 'Group A',
            'matching_enabled' => true,
            'location_lat' => 40.0,
            'location_lon' => -74.0,
            'created_by_user_id' => $user->id,
            'creator_id' => $user->id,
        ]);

        $groupA->members()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Target Group
        $groupB = Group::factory()->create([
            'name' => 'Group B',
            'matching_enabled' => true,
            'location_lat' => 40.1,
            'location_lon' => -74.1,
            'created_by_user_id' => User::factory()->create()->id,
        ]);

        // Request Match
        $response = $this->actingAs($user)->postJson("/api/groups/{$groupA->id}/matches/{$groupB->id}/connect");

        $response->assertStatus(201)
                 ->assertJsonPath('match.status', 'pending');

        $this->assertDatabaseHas('group_matches', [
            'group_id_1' => $groupA->id,
            'group_id_2' => $groupB->id,
            'status' => 'pending',
            'initiated_by_user_id' => $user->id,
        ]);

        // Verify cannot request again
        $response2 = $this->actingAs($user)->postJson("/api/groups/{$groupA->id}/matches/{$groupB->id}/connect");
        $response2->assertStatus(400);
    }
}
