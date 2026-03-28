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
        Storage::fake('public');
        
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
        $encryptionService->encryptAndStore($content, $path, 'public');
        
        $photo = Photo::create([
            'user_id' => $user->id,
            'filename' => 'secret.jpg',
            'original_filename' => 'original_secret.jpg',
            'file_path' => $path,
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

        \App\Models\RelationshipTier::create([
            'match_id' => $match->id,
            'current_tier' => 'connected',
        ]);

        // Instantiate controller
        $mediaAnalysisMock = new class implements \App\Services\MediaAnalysis\MediaAnalysisInterface {
            public function analyze(string $url, string $type): \App\Services\MediaAnalysis\MediaAnalysisResult {
                return new \App\Services\MediaAnalysis\MediaAnalysisResult(true, []);
            }
            public function compareFaces(string $sourcePath, string $targetPath): float {
                return 1.0;
            }
        };
        $controller = new \App\Http\Controllers\PhotoController($mediaAnalysisMock);
        
        // Mock Request
        $request = \Illuminate\Http\Request::create('/dummy', 'GET');
        
        // Authenticate as matchUser
        $this->actingAs($matchUser);

        // Call method
        $response = $controller->original($request, $photo->id);
        
        // Assert response is a StreamedResponse
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->fail('Expected StreamedResponse, got JsonResponse: ' . json_encode($response->getData()));
        }
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }
}
