<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use App\Models\UserMatch;
use App\Models\PhotoReveal;
use App\Services\PhotoEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoRevealControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_reveal_controller_decryption_logic()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $matchUser = User::factory()->create();
        
        // Create a match
        $match = UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $matchUser->id,
            'status' => 'matched',
        ]);

        // Create an encrypted photo
        $content = 'This is a secret photo content';
        $path = 'photos/secret.jpg';
        
        $encryptionService = new PhotoEncryptionService();
        $encryptionService->encryptAndStore($content, $path, 'local');
        
        $photo = Photo::create([
            'user_id' => $user->id,
            'filename' => 'secret.jpg',
            'original_filename' => 'original_secret.jpg',
            'original_path' => $path,
            'is_encrypted' => true,
            'mime_type' => 'text/plain',
            'file_size' => strlen($content),
            'width' => 100,
            'height' => 100,
            'sort_order' => 0,
        ]);

        // Reveal it
        PhotoReveal::create([
            'user_id' => $matchUser->id,
            'match_id' => $match->id,
            'photo_id' => $photo->id,
            'status' => 'active',
        ]);

        // Instantiate controller
        $controller = new \App\Http\Controllers\PhotoRevealController();
        
        // Mock Request
        $request = \Illuminate\Http\Request::create('/dummy', 'GET');
        $request->setUserResolver(function () use ($matchUser) {
            return $matchUser;
        });

        // Call method
        $response = $controller->original($request, $photo, $encryptionService);
        
        // Assert response is a StreamedResponse and content matches
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        
        // Capture output
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();
        
        $this->assertEquals($content, $output);
    }
}
