<?php

namespace Tests\Feature;

use App\Models\Chatroom;
use App\Models\ChatroomMessage;
use App\Models\User;
use App\Services\ContentModerationService;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class ChatroomMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Force register rate limiter to debug AppServiceProvider issue
        \Illuminate\Support\Facades\RateLimiter::for('messaging', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60);
        });
        
        // Mock ContentModerationService
        $this->mock(ContentModerationService::class, function ($mock) {
            $mock->shouldReceive('moderateContent')->andReturn(['flagged' => false]);
        });

        // Mock TelemetryService
        $this->mock(TelemetryService::class, function ($mock) {
            $mock->shouldReceive('emit')->andReturn(true);
        });
    }

    public function test_user_can_send_message()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create();
        $chatroom->addMember($user, 'member');

        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/messages", [
                'content' => 'Hello world',
                'type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'content',
                'user_id',
                'chatroom_id',
            ]);

        $this->assertDatabaseHas('chatroom_messages', [
            'content' => 'Hello world',
            'user_id' => $user->id,
            'chatroom_id' => $chatroom->id,
        ]);
    }

    public function test_user_can_list_messages()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create();
        $chatroom->addMember($user, 'member');

        ChatroomMessage::factory()->count(3)->create([
            'chatroom_id' => $chatroom->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/chatrooms/{$chatroom->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    public function test_non_member_cannot_send_message()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create();
        // User is NOT added as member

        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/messages", [
                'content' => 'Hello world',
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'You are not a member of this chatroom']);
    }

    public function test_muted_user_cannot_send_message()
    {
        $user = User::factory()->create();
        $chatroom = Chatroom::factory()->create();
        $chatroom->addMember($user, 'member');
        $chatroom->muteMember($user);

        $response = $this->actingAs($user)
            ->postJson("/api/chatrooms/{$chatroom->id}/messages", [
                'content' => 'Hello world',
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'You are muted in this chatroom']);
    }
}
