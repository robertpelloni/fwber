<?php

namespace Tests\Feature;

use App\Models\Chatroom;
use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class ChatroomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ContentModerationService
        $this->mock(ContentModerationService::class, function ($mock) {
            $mock->shouldReceive('checkContent')->andReturn(true);
        });
    }

    public function test_user_can_create_chatroom()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/chatrooms', [
                'name' => 'Test Chatroom',
                'description' => 'A test chatroom',
                'type' => 'interest',
                'category' => 'Technology',
                'is_public' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'type',
                'created_by',
            ]);

        $this->assertDatabaseHas('chatrooms', [
            'name' => 'Test Chatroom',
            'type' => 'interest',
        ]);
    }

    public function test_user_can_list_chatrooms()
    {
        $user = User::factory()->create();
        Chatroom::factory()->count(3)->create([
            'is_public' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/chatrooms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    public function test_user_can_join_chatroom()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create([
            'is_public' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/join");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully joined chatroom']);

        $this->assertDatabaseHas('chatroom_members', [
            'chatroom_id' => $chatroom->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_join_chatroom_twice()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create([
            'is_public' => true,
        ]);

        // Join first time
        $chatroom->addMember($user, 'member');

        // Try to join again
        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/join");

        $response->assertStatus(400)
            ->assertJson(['message' => 'You are already a member of this chatroom']);
    }

    public function test_user_can_leave_chatroom()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create([
            'is_public' => true,
        ]);

        $chatroom->addMember($user, 'member');

        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/leave");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully left chatroom']);

        $this->assertDatabaseMissing('chatroom_members', [
            'chatroom_id' => $chatroom->id,
            'user_id' => $user->id,
            'left_at' => null, // Assuming soft delete or status change, but controller uses removeMember which might delete or update
        ]);
    }
}
