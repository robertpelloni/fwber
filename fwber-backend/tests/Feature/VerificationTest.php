<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_check_verification_status()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'is_verified' => false]);

        $response = $this->actingAs($user)->getJson('/api/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'is_verified' => false,
                'verified_at' => null,
            ]);
    }

    public function test_user_can_verify_successfully()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'is_verified' => false]);

        // Create a primary photo for the user
        Photo::create([
            'user_id' => $user->id,
            'filename' => 'profile.jpg',
            'original_filename' => 'profile.jpg',
            'file_path' => 'photos/' . $user->id . '/profile.jpg',
            'thumbnail_path' => 'photos/' . $user->id . '/thumbnails/thumb_profile.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'width' => 800,
            'height' => 800,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        // Mock storage for the primary photo
        Storage::disk('public')->put('photos/' . $user->id . '/profile.jpg', 'dummy content');

        // Upload a selfie
        $file = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($user)->postJson('/api/verification/verify', [
            'photo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'verified' => true,
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'is_verified' => true,
        ]);
    }

    public function test_verification_fails_with_mismatch()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'is_verified' => false]);

        // Create a primary photo with "mismatch" in the path to trigger the mock driver failure
        Photo::create([
            'user_id' => $user->id,
            'filename' => 'mismatch_profile.jpg',
            'original_filename' => 'mismatch_profile.jpg',
            'file_path' => 'photos/' . $user->id . '/mismatch_profile.jpg',
            'thumbnail_path' => 'photos/' . $user->id . '/thumbnails/thumb_mismatch_profile.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'width' => 800,
            'height' => 800,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        Storage::disk('public')->put('photos/' . $user->id . '/mismatch_profile.jpg', 'dummy content');

        // Upload a selfie
        $file = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($user)->postJson('/api/verification/verify', [
            'photo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'verified' => false,
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'is_verified' => false,
        ]);
    }

    public function test_verification_requires_primary_photo()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // No photos created

        $file = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->actingAs($user)->postJson('/api/verification/verify', [
            'photo' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Please upload a profile photo first']);
    }
}
