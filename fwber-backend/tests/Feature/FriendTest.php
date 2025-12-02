<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Friend;

class FriendTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_get_their_friends_list()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);
        Friend::factory()->create(['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'accepted']);

        $response = $this->actingAs($user)->getJson('/api/friends');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_a_user_can_get_their_friend_requests()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson('/api/friends/requests');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_a_user_can_send_a_friend_request()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/friends/requests', ['friend_id' => $friend->id]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('friends', ['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'pending']);
    }

    public function test_a_user_can_accept_a_friend_request()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->postJson("/api/friends/requests/{$friend->id}", ['status' => 'accepted']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('friends', ['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('friends', ['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'accepted']);
    }

    public function test_a_user_can_decline_a_friend_request()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->postJson("/api/friends/requests/{$friend->id}", ['status' => 'declined']);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('friends', ['user_id' => $friend->id, 'friend_id' => $user->id]);
    }

    public function test_a_user_can_remove_a_friend()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);
        Friend::factory()->create(['user_id' => $friend->id, 'friend_id' => $user->id, 'status' => 'accepted']);

        $response = $this->actingAs($user)->deleteJson("/api/friends/{$friend->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('friends', ['user_id' => $user->id, 'friend_id' => $friend->id]);
        $this->assertDatabaseMissing('friends', ['user_id' => $friend->id, 'friend_id' => $user->id]);
    }

    public function test_a_user_can_search_for_other_users()
    {
        $user = User::factory()->create();
        $userToFind = User::factory()->create(['name' => 'John Doe']);

        $response = $this->actingAs($user)->getJson('/api/friends/search?q=John');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'John Doe']);
    }
}
