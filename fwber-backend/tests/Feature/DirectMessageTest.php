<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\RelationshipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DirectMessageTest extends TestCase
{
    use RefreshDatabase;

    protected $sender;
    protected $receiver;
    protected $match;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sender = User::factory()->create();
        $this->receiver = User::factory()->create();
        
        // Create an active match
        $this->match = UserMatch::create([
            'user1_id' => $this->sender->id,
            'user2_id' => $this->receiver->id,
            'is_active' => true,
            // 'matched_at' => now(), // Removed as column doesn't exist
        ]);
        
        // Create initial relationship tier
        RelationshipTier::create([
            'match_id' => $this->match->id,
            'current_tier' => 'matched',
            'first_matched_at' => now(),
        ]);
    }

    public function test_can_send_text_message()
    {
        $response = $this->actingAs($this->sender)->postJson('/api/messages', [
            'receiver_id' => $this->receiver->id,
            'content' => 'Hello there!',
            'message_type' => 'text',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'content' => 'Hello there!',
            'message_type' => 'text',
        ]);
    }

    public function test_can_send_audio_message()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('voice.mp3', 100, 'audio/mpeg');

        $response = $this->actingAs($this->sender)->postJson('/api/messages', [
            'receiver_id' => $this->receiver->id,
            'message_type' => 'audio',
            'media' => $file,
            'media_duration' => 15,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'message_type' => 'audio',
            'media_duration' => 15,
        ]);
        
        // Verify file storage
        // Note: The controller stores it in messages/{senderId}/{hash}.mp3
        // We can't easily predict the hash, but we can check if the directory exists
        // or check the database record for the path
        $message = \App\Models\Message::latest()->first();
        $this->assertNotNull($message->media_url);
    }

    public function test_audio_message_validation_duration()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('voice.mp3', 100, 'audio/mpeg');

        // Test too long
        $response = $this->actingAs($this->sender)->postJson('/api/messages', [
            'receiver_id' => $this->receiver->id,
            'message_type' => 'audio',
            'media' => $file,
            'media_duration' => 121, // Max is 120
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['media_duration']);
    }

    public function test_cannot_send_message_without_match()
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($this->sender)->postJson('/api/messages', [
            'receiver_id' => $stranger->id,
            'content' => 'Hello stranger',
        ]);

        $response->assertStatus(404); // Or 403 depending on implementation, controller says 404
    }
}
