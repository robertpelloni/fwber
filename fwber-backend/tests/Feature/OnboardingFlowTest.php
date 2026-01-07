<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_then_list_photos_returns_photo()
    {
        config(['app.avatar_mode' => 'upload']);
        Storage::fake('public');
        
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->image('photo.jpg');
        $response = $this->actingAs($user)->postJson('/api/photos', [
            'photo' => $file,
            'is_private' => false
        ]);
        
        $response->assertStatus(201);
        
        $listResponse = $this->actingAs($user)->getJson('/api/photos');
        
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1, 'data');
        // The controller might use UUIDs or hash names, so just check the extension and existence
        $this->assertStringEndsWith('.jpg', $listResponse->json('data.0.filename'));
        $this->assertNotNull($listResponse->json('data.0.id'));
    }
}
