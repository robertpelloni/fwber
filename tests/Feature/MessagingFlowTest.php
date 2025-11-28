<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\Message;
use App\Models\Block;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $match;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two users with complete profiles (required for matching usually, but good practice)
        $this->user1 = User::factory()->create();
        $this->user1->profile()->create([
            'bio' => 'User 1 Bio',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'location_description' => 'New York, NY',
        ]);

        $this->user2 = User::factory()->create();
        $this->user2->profile()->create([
            'bio' => 'User 2 Bio',
            'birthdate' => '1992-01-01',
            'gender' => 'female',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'location_description' => 'New York, NY',
        ]);

        // Establish a match between them
        $this->match = UserMatch::create([
            'user1_id' => $this->user1->id,
            'user2_id' => $this->user2->id,
            'is_active' => true,
            'matched_at' => now(),
        ]);
    }

    public function test_users_can_send_text_messages_when_matched()
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/messages', [
                'receiver_id' => $this->user2->id,
                'content' => 'Hello there!',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message' => [
                    'id',
                    'sender_id',
                    'receiver_id',
                    'content',
                    'created_at',
                ],
                'tier_update',
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'content' => 'Hello there!',
        ]);
    }

    public function test_users_cannot_message_if_not_matched()
    {
        $user3 = User::factory()->create();
        
        $response = $this->actingAs($this->user1)
            ->postJson('/api/messages', [
                'receiver_id' => $user3->id,
                'content' => 'Hello stranger!',
            ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'No active match found']);
    }

    public function test_users_can_retrieve_conversation()
    {
        // Seed some messages
        Message::create([
            'sender_id' => $this->user1->id,
            'receiver_id' => $this->user2->id,
            'content' => 'Message 1',
            'sent_at' => now()->subMinutes(5),
        ]);

        Message::create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'content' => 'Message 2',
            'sent_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson("/api/messages/{$this->user2->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'messages' => [
                    '*' => ['id', 'content', 'sender_id', 'receiver_id']
                ],
                'pagination',
                'other_user',
            ]);
            
        $this->assertCount(2, $response->json('messages'));
    }

    public function test_users_can_mark_messages_as_read()
    {
        $message = Message::create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'content' => 'Read me',
            'sent_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/messages/{$message->id}/read");

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_unread_count_is_accurate()
    {
        // Create 3 unread messages for user1
        Message::factory()->count(3)->create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'is_read' => false,
        ]);

        // Create 1 read message for user1
        Message::factory()->create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/messages/unread-count');

        $response->assertStatus(200)
            ->assertJson(['unread_count' => 3]);
    }

    public function test_blocked_users_cannot_message()
    {
        // User 2 blocks User 1
        Block::create([
            'blocker_id' => $this->user2->id,
            'blocked_id' => $this->user1->id,
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/messages', [
                'receiver_id' => $this->user2->id,
                'content' => 'Can you hear me?',
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Messaging blocked between users']);
    }

    public function test_users_can_mark_all_messages_as_read()
    {
        // Create 3 unread messages
        Message::factory()->count(3)->create([
            'sender_id' => $this->user2->id,
            'receiver_id' => $this->user1->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/messages/mark-all-read/{$this->user2->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'All messages marked as read']);

        $this->assertDatabaseMissing('messages', [
            'receiver_id' => $this->user1->id,
            'sender_id' => $this->user2->id,
            'is_read' => false,
        ]);
    }
}
