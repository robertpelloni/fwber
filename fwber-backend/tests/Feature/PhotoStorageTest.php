<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
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
}
