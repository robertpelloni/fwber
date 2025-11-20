<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // Allow photo uploads for this test
        Config::set('app.avatar_mode', 'upload');
    }

    public function test_photo_upload_stores_files_and_generates_thumbnails()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 800, 800);

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
                'is_private' => false,
            ]);

        $response->assertStatus(201);

        $photoId = $response->json('data.id');
        $photo = Photo::find($photoId);

        $this->assertNotNull($photo);
        
        // Check if files exist in storage
        Storage::disk('public')->assertExists($photo->file_path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);

        // Check URL generation
        // The model should now use Storage::url()
        $expectedUrl = Storage::disk('public')->url($photo->file_path);
        $this->assertEquals($expectedUrl, $photo->url);
    }

    public function test_photo_url_generation_consistency()
    {
        $user = User::factory()->create();
        $photo = Photo::create([
            'user_id' => $user->id,
            'filename' => 'test.jpg',
            'original_filename' => 'test.jpg',
            'file_path' => 'photos/' . $user->id . '/test.jpg',
            'thumbnail_path' => 'photos/' . $user->id . '/thumbnails/thumb_test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'width' => 800,
            'height' => 800,
            'is_primary' => true,
            'is_private' => false,
            'sort_order' => 0,
            'metadata' => [],
        ]);

        // Check what the model returns vs what Storage facade returns
        $storageUrl = Storage::disk('public')->url($photo->file_path);
        $modelUrl = $photo->url;

        $this->assertEquals($storageUrl, $modelUrl);
    }

    public function test_face_blur_applied_event_emitted_with_metadata()
    {
        Log::spy();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('blurred.jpg', 800, 800);

        $metadata = [
            'facesDetected' => 2,
            'blurApplied' => true,
            'processingTimeMs' => 120,
            'originalFileName' => 'blurred.jpg',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
                'face_blur_metadata' => json_encode($metadata),
            ]);

        $response->assertStatus(201);

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($user) {
            return $message === 'Telemetry event'
                && ($context['event'] ?? null) === 'face_blur_applied'
                && ($context['payload']['user_id'] ?? null) === $user->id
                && ($context['payload']['faces_detected'] ?? null) === 2;
        });
    }

    public function test_face_blur_skipped_event_emitted_when_reason_provided()
    {
        Log::spy();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('original.jpg', 800, 800);

        $metadata = [
            'facesDetected' => 0,
            'blurApplied' => false,
            'skippedReason' => 'no_faces_detected',
            'originalFileName' => 'original.jpg',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
                'face_blur_metadata' => json_encode($metadata),
            ]);

        $response->assertStatus(201);

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($user) {
            return $message === 'Telemetry event'
                && ($context['event'] ?? null) === 'face_blur_skipped_reason'
                && ($context['payload']['user_id'] ?? null) === $user->id
                && ($context['payload']['reason'] ?? null) === 'no_faces_detected';
        });
    }
}
