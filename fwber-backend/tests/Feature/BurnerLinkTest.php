<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\BurnerLink;
use App\Models\Chatroom;
use Illuminate\Support\Str;

class BurnerLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_burner_link()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/burner-links', [
            'expires_in_hours' => 24
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'expires_at',
                'url'
            ]);

        $this->assertDatabaseHas('burner_links', [
            'creator_id' => $user->id,
            'used_at' => null
        ]);
    }

    public function test_user_can_join_burner_chat()
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();

        $burnerLink = BurnerLink::create([
            'creator_id' => $creator->id,
            'token' => Str::random(32),
            'expires_at' => now()->addHours(24),
            'used_at' => null
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/burner-links/{$burnerLink->token}/join");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'chatroom_id'
            ]);

        $this->assertDatabaseHas('burner_links', [
            'id' => $burnerLink->id,
        ]);
        $this->assertNotNull(BurnerLink::find($burnerLink->id)->used_at);

        $chatroomId = $response->json('chatroom_id');

        $this->assertDatabaseHas('chatroom_members', [
            'chatroom_id' => $chatroomId,
            'user_id' => $creator->id
        ]);

        $this->assertDatabaseHas('chatroom_members', [
            'chatroom_id' => $chatroomId,
            'user_id' => $joiner->id
        ]);
    }

    public function test_creator_cannot_join_own_burner_link()
    {
        $creator = User::factory()->create();

        $burnerLink = BurnerLink::create([
            'creator_id' => $creator->id,
            'token' => Str::random(32),
            'expires_at' => now()->addHours(24),
            'used_at' => null
        ]);

        $response = $this->actingAs($creator)->postJson("/api/burner-links/{$burnerLink->token}/join");

        $response->assertStatus(400)
            ->assertJson(['message' => 'You cannot scan your own burner link.']);
    }

    public function test_cannot_join_expired_burner_link()
    {
        $creator = User::factory()->create();
        $joiner = User::factory()->create();

        $burnerLink = BurnerLink::create([
            'creator_id' => $creator->id,
            'token' => Str::random(32),
            'expires_at' => now()->subHour(),
            'used_at' => null
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/burner-links/{$burnerLink->token}/join");

        $response->assertStatus(403)
            ->assertJson(['message' => 'This burner link has expired.']);
    }

    public function test_cannot_join_used_burner_link()
    {
        $creator = User::factory()->create();
        $joiner1 = User::factory()->create();
        $joiner2 = User::factory()->create();

        $burnerLink = BurnerLink::create([
            'creator_id' => $creator->id,
            'token' => Str::random(32),
            'expires_at' => now()->addHours(24),
            'used_at' => now()
        ]);

        $response = $this->actingAs($joiner2)->postJson("/api/burner-links/{$burnerLink->token}/join");

        $response->assertStatus(403)
            ->assertJson(['message' => 'This burner link has already been used.']);
    }
}