<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationWithAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_avatar_and_photo_record_is_created(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->post('/api/auth/register', [
            'name' => 'Avatar User',
            'email' => 'avatar@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'avatar' => $file,
            'profile' => [
                'bio' => 'I have an avatar',
            ],
        ]);

        $response->assertCreated();
        
        $user = User::where('email', 'avatar@example.com')->first();
        $this->assertNotNull($user);

        // Check profile has avatar_url
        $this->assertNotNull($user->profile->avatar_url);
        
        // Check file exists in storage
        // The path in avatar_url is a full URL, we need to extract the relative path
        // Assuming avatar_url is like http://localhost/storage/photos/1/hash.jpg
        // and storage path is public/photos/1/hash.jpg
        // But Storage::url() usually prepends /storage/
        
        // Let's find the Photo record first to get the exact path
        $photo = $user->photos()->where('is_primary', true)->first();
        $this->assertNotNull($photo);
        
        // Verify the file exists on the public disk at the path stored in the Photo model
        Storage::disk('public')->assertExists($photo->file_path);

        // Check Photo model creation details
        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
            'is_primary' => true,
            'original_filename' => 'avatar.jpg',
        ]);
        
        $this->assertEquals($user->profile->avatar_url, Storage::url($photo->file_path));
    }
}
