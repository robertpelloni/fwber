<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_friends_and_pending_requests(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create(['name' => 'Friend User']);
        $requester = User::factory()->create(['name' => 'Request User']);

        Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);
        Friend::create([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
            'status' => 'accepted',
        ]);
        Friend::create([
            'user_id' => $requester->id,
            'friend_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->getJson('/api/friends')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Friend User']);

        $this->actingAs($user)->getJson('/api/friends/requests')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Request User']);
    }

    public function test_user_can_search_send_and_accept_friend_request(): void
    {
        $user = User::factory()->create(['name' => 'Primary User', 'email' => 'primary@example.com']);
        $target = User::factory()->create(['name' => 'Search User', 'email' => 'search@example.com']);

        $this->actingAs($user)->getJson('/api/users/search?q=Search')
            ->assertOk()
            ->assertJsonFragment(['email' => 'search@example.com']);

        $this->actingAs($user)->postJson('/api/friends/requests', [
            'friend_id' => $target->id,
        ])->assertStatus(201);

        $this->actingAs($target)->postJson("/api/friends/requests/{$user->id}", [
            'status' => 'accepted',
        ])->assertOk();

        $this->assertDatabaseHas('friends', [
            'user_id' => $user->id,
            'friend_id' => $target->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('friends', [
            'user_id' => $target->id,
            'friend_id' => $user->id,
            'status' => 'accepted',
        ]);
    }
}
