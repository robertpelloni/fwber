<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_upload_blocked_when_generated_only_mode(): void
    {
        Config::set('app.avatar_mode', 'generated-only');
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Photo uploads disabled. Using generated avatars only.',
                'avatar_mode' => 'generated-only',
            ]);

        // Verify no photo was stored
        Storage::disk('public')->assertMissing('photos/' . $file->hashName());
    }

    public function test_photo_upload_allowed_when_flexible_mode(): void
    {
        Config::set('app.avatar_mode', 'flexible');
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'photo' => ['id', 'url', 'created_at'],
            ]);

        // Verify photo was stored
        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
        ]);
    }

    public function test_default_avatar_mode_is_generated_only(): void
    {
        // Don't explicitly set avatar_mode, use default
        Config::set('app.avatar_mode', null);
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/photos', [
                'photo' => $file,
            ]);

        // Should default to generated-only and block upload
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Photo uploads disabled. Using generated avatars only.',
                'avatar_mode' => 'generated-only',
            ]);
    }

    public function test_unauthenticated_user_cannot_upload_photos_regardless_of_mode(): void
    {
        Config::set('app.avatar_mode', 'flexible');
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/photos', [
            'photo' => $file,
        ]);

        $response->assertStatus(401);
    }
}
