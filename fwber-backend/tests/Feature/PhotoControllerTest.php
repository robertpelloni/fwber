<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_photo()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson('/api/photos', [
            'photo' => $file,
            'is_private' => false
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
            'original_filename' => 'photo.jpg'
        ]);
        
        // Verify file storage
        $photo = Photo::first();
        Storage::disk('public')->assertExists($photo->file_path);
    }

    public function test_user_can_list_photos()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Photo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/photos');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_user_can_delete_photo()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/photos/{$photo->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_user_can_update_photo()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id, 'is_private' => false]);

        $response = $this->actingAs($user)->putJson("/api/photos/{$photo->id}", [
            'is_private' => true
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'is_private' => true
        ]);
    }
}
