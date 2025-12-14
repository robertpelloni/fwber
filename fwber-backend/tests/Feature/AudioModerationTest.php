<?php

namespace Tests\Feature;

use App\Jobs\TranscribeAudioMessage;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\AudioTranscriptionService;
use App\Services\Ai\ContentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AudioModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_message_is_transcribed_and_moderated()
    {
        Storage::fake('public');
        
        // Create users
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Create a message with audio
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message_type' => 'audio',
            'media_url' => '/storage/audio/test.mp3',
            'media_type' => 'audio/mpeg',
            'media_duration' => 10,
        ]);

        // Create a dummy file
        Storage::disk('public')->put('audio/test.mp3', 'dummy content');

        // Mock Transcription Service
        $transcriptionService = Mockery::mock(AudioTranscriptionService::class);
        $transcriptionService->shouldReceive('transcribe')
            ->once()
            ->andReturn('This is a test message with some bad content.');

        // Mock Moderation Service
        $moderationService = Mockery::mock(ContentModerationService::class);
        $moderationService->shouldReceive('moderate')
            ->once()
            ->with('This is a test message with some bad content.')
            ->andReturn([
                'flagged' => true,
                'categories' => ['hate', 'violence'],
            ]);

        // Run the job
        $job = new TranscribeAudioMessage($message);
        $job->handle($transcriptionService, $moderationService);

        // Refresh message
        $message->refresh();

        // Assertions
        $this->assertEquals('This is a test message with some bad content.', $message->transcription);
        $this->assertTrue($message->is_flagged);
        $this->assertEquals('hate, violence', $message->flagged_reason);
    }

    public function test_audio_message_is_not_flagged_if_safe()
    {
        Storage::fake('public');
        
        // Create users
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Create a message with audio
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message_type' => 'audio',
            'media_url' => '/storage/audio/safe.mp3',
            'media_type' => 'audio/mpeg',
            'media_duration' => 5,
        ]);

        // Create a dummy file
        Storage::disk('public')->put('audio/safe.mp3', 'dummy content');

        // Mock Transcription Service
        $transcriptionService = Mockery::mock(AudioTranscriptionService::class);
        $transcriptionService->shouldReceive('transcribe')
            ->once()
            ->andReturn('Hello, how are you?');

        // Mock Moderation Service
        $moderationService = Mockery::mock(ContentModerationService::class);
        $moderationService->shouldReceive('moderate')
            ->once()
            ->with('Hello, how are you?')
            ->andReturn([
                'flagged' => false,
                'categories' => [],
            ]);

        // Run the job
        $job = new TranscribeAudioMessage($message);
        $job->handle($transcriptionService, $moderationService);

        // Refresh message
        $message->refresh();

        // Assertions
        $this->assertEquals('Hello, how are you?', $message->transcription);
        $this->assertFalse($message->is_flagged);
        $this->assertNull($message->flagged_reason);
    }
}
