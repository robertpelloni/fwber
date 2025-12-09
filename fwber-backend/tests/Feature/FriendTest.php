<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_get_their_friends_list()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);

        $response = $this->actingAs($user)->getJson('/api/venue/friends');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_a_user_can_get_their_friend_requests()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        Friend::factory()->create(['user_id' => $requester->id, 'friend_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson('/api/venue/friends/requests');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_a_user_can_send_a_friend_request()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/venue/friends/requests', ['friend_id' => $friend->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('friends', ['user_id' => $user->id, 'friend_id' => $friend->id]);
    }

    public function test_a_user_can_respond_to_a_friend_request()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        $friendRequest = Friend::factory()->create(['user_id' => $requester->id, 'friend_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->postJson("/api/venue/friends/requests/{$requester->id}", ['status' => 'accepted']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('friends', ['id' => $friendRequest->id, 'status' => 'accepted']);
    }

    public function test_a_user_can_remove_a_friend()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $friendship = Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);

        $response = $this->actingAs($user)->deleteJson("/api/venue/friends/{$friend->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('friends', ['id' => $friendship->id]);
    }

    public function test_a_user_can_search_for_other_users()
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($user)->getJson('/api/venue/friends/search?query=John');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'John Doe']);
        $response->assertJsonMissing(['name' => 'Jane Doe']);
    }
}
