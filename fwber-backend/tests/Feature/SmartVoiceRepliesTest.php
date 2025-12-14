<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\Message;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\DTOs\LlmResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class SmartVoiceRepliesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $match;
    protected $matchUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable the feature
        config(['features.ai_wingman' => true]);
        
        $this->user = User::factory()->create();
        $this->matchUser = User::factory()->create();
        
        $this->match = UserMatch::create([
            'user1_id' => $this->user->id,
            'user2_id' => $this->matchUser->id,
            'is_active' => true,
        ]);
    }

    public function test_reply_suggestions_include_transcription()
    {
        // Create a message with transcription
        Message::create([
            'sender_id' => $this->matchUser->id,
            'receiver_id' => $this->user->id,
            'content' => null,
            'message_type' => 'audio',
            'transcription' => 'Hey, do you want to grab coffee?',
            'media_url' => 'http://example.com/audio.mp3',
            'media_duration' => 10,
        ]);

        // Mock Driver
        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->withArgs(function($messages, $options) {
                $prompt = $messages[1]['content'];
                return str_contains($prompt, 'Match: Hey, do you want to grab coffee?');
            })
            ->once()
            ->andReturn(new LlmResponse('["Sure, I would love to!", "Coffee sounds great.", "When were you thinking?"]', 'mock'));

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')->andReturn($mockDriver);

        $this->app->instance(LlmManager::class, $mockLlmManager);

        $response = $this->actingAs($this->user)
            ->getJson("/api/wingman/replies/{$this->matchUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['suggestions']);
    }

    public function test_reply_suggestions_fallback_for_audio_without_transcription()
    {
        // Create a message without transcription
        Message::create([
            'sender_id' => $this->matchUser->id,
            'receiver_id' => $this->user->id,
            'content' => null,
            'message_type' => 'audio',
            'transcription' => null,
            'media_url' => 'http://example.com/audio.mp3',
            'media_duration' => 10,
        ]);

        // Mock Driver
        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->withArgs(function($messages, $options) {
                $prompt = $messages[1]['content'];
                return str_contains($prompt, 'Match: [Audio Message]');
            })
            ->once()
            ->andReturn(new LlmResponse('["I listened to your message.", "Can you type that out?", "Nice voice!"]', 'mock'));

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')->andReturn($mockDriver);

        $this->app->instance(LlmManager::class, $mockLlmManager);

        $response = $this->actingAs($this->user)
            ->getJson("/api/wingman/replies/{$this->matchUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['suggestions']);
    }
}
