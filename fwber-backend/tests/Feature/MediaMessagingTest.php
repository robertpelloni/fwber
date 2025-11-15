<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMatch;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaMessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;
    private User $receiver;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');

        // Create sender and receiver with profiles
        $this->sender = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->sender->id,
            'display_name' => 'Sender',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'bio' => 'Sender bio',
        ]);

        $this->receiver = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->receiver->id,
            'display_name' => 'Receiver',
            'date_of_birth' => now()->subYears(26),
            'gender' => 'female',
            'bio' => 'Receiver bio',
        ]);

        // Create match
        UserMatch::create([
            'user1_id' => min($this->sender->id, $this->receiver->id),
            'user2_id' => max($this->sender->id, $this->receiver->id),
            'is_active' => true,
        ]);

        $this->token = ApiToken::generateForUser($this->sender, 'test');
    }

    #[Test]
    public function can_send_voice_message_with_audio_file(): void
    {
        $audioFile = UploadedFile::fake()->create('voice.mp3', 500, 'audio/mpeg');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'content' => 'Voice message',
                'message_type' => 'audio',
                'media' => $audioFile,
                'media_duration' => 15, // 15 seconds
            ]);

        $response->assertStatus(201);

        $message = Message::latest()->first();
        $this->assertEquals('audio', $message->message_type);
        $this->assertNotNull($message->media_url);
        $this->assertEquals('audio/mpeg', $message->media_type);
        $this->assertEquals(15, $message->media_duration);

        // Verify file stored
        Storage::disk('public')->assertExists(
            str_replace('/storage/', '', parse_url($message->media_url, PHP_URL_PATH))
        );
    }

    #[Test]
    public function can_send_image_message(): void
    {
        $imageFile = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'content' => 'Check this out!',
                'message_type' => 'image',
                'media' => $imageFile,
            ]);

        $response->assertStatus(201);

        $message = Message::latest()->first();
        $this->assertEquals('image', $message->message_type);
        $this->assertNotNull($message->media_url);
        $this->assertStringContainsString('image/', $message->media_type);
        $this->assertEquals('Check this out!', $message->content);
    }

    #[Test]
    public function can_send_video_message(): void
    {
        $videoFile = UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'message_type' => 'video',
                'media' => $videoFile,
                'media_duration' => 30,
            ]);

        $response->assertStatus(201);

        $message = Message::latest()->first();
        $this->assertEquals('video', $message->message_type);
        $this->assertNotNull($message->media_url);
        $this->assertEquals('video/mp4', $message->media_type);
        $this->assertEquals(30, $message->media_duration);
    }

    #[Test]
    public function text_message_still_works_without_media(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'content' => 'Plain text message',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201);

        $message = Message::latest()->first();
        $this->assertEquals('text', $message->message_type);
        $this->assertNull($message->media_url);
        $this->assertNull($message->media_type);
        $this->assertEquals('Plain text message', $message->content);
    }

    #[Test]
    public function media_file_too_large_returns_validation_error(): void
    {
        $hugeFile = UploadedFile::fake()->create('huge.mp4', 60000, 'video/mp4'); // 60MB

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'message_type' => 'video',
                'media' => $hugeFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('media');
    }

    #[Test]
    public function voice_duration_exceeds_max_returns_validation_error(): void
    {
        $audioFile = UploadedFile::fake()->create('long_voice.mp3', 500, 'audio/mpeg');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/messages', [
                'receiver_id' => $this->receiver->id,
                'message_type' => 'audio',
                'media' => $audioFile,
                'media_duration' => 400, // Over 5 min limit
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('media_duration');
    }

    #[Test]
    public function conversation_includes_media_messages(): void
    {
        // Send mixed messages
        Message::create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'content' => 'Text message',
            'message_type' => 'text',
            'sent_at' => now()->subMinutes(3),
        ]);

        Message::create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'content' => 'Voice message',
            'message_type' => 'audio',
            'media_url' => '/storage/messages/1/voice.mp3',
            'media_type' => 'audio/mpeg',
            'media_duration' => 10,
            'sent_at' => now()->subMinutes(2),
        ]);

        Message::create([
            'sender_id' => $this->receiver->id,
            'receiver_id' => $this->sender->id,
            'content' => 'Photo reply',
            'message_type' => 'image',
            'media_url' => '/storage/messages/2/photo.jpg',
            'media_type' => 'image/jpeg',
            'sent_at' => now()->subMinutes(1),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/messages/{$this->receiver->id}");

        $response->assertOk();
        
        $messages = $response->json('messages');
        $this->assertCount(3, $messages);

        // Verify text message
        $this->assertEquals('text', $messages[0]['message_type']);
        $this->assertNull($messages[0]['media_url']);

        // Verify audio message
        $this->assertEquals('audio', $messages[1]['message_type']);
        $this->assertNotNull($messages[1]['media_url']);
        $this->assertEquals(10, $messages[1]['media_duration']);

        // Verify image message
        $this->assertEquals('image', $messages[2]['message_type']);
        $this->assertNotNull($messages[2]['media_url']);
    }
}
