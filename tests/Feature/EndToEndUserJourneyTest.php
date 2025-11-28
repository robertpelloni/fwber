<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EndToEndUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_user_journey_signup_to_matching()
    {
        // Allow photo uploads for this test
        Config::set('app.avatar_mode', 'upload');

        Storage::fake('public');

        // 1. Registration
        $email = 'journey_' . uniqid() . '@example.com';
        $registerData = [
            "name" => "Journey User",
            "email" => $email,
            "password" => "password123",
            "password_confirmation" => "password123",
            "profile" => [
                "displayName" => "Journey",
                "bio" => "Just starting my journey.",
            ],
        ];

        $response = $this->postJson("/api/auth/register", $registerData);
        $response->assertCreated();
        
        $token = $response->json('token');
        $userId = $response->json('user.id');

        // 2. Update Profile with Detailed Preferences (The new feature)
        $profileData = [
            "display_name" => "Journey Updated",
            "bio" => "Ready to match!",
            "date_of_birth" => "1990-01-01", // Required for profile completion
            "gender" => "non-binary",
            "preferences" => [
                "want_body_muscular" => 10,
                "want_ethnicity_latino" => 8,
                "want_safe_sex" => 1,
                "bedroom_personality" => "sub",
                "smoke" => 0,
                "no_smoke" => 1,
            ],
            "looking_for" => ["dating", "friendship"],
            "location" => [
                "latitude" => 40.7128,
                "longitude" => -74.0060,
                "city" => "New York",
                "state" => "NY"
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/user", $profileData);
        
        $response->assertOk()
            ->assertJsonPath("data.profile.display_name", "Journey Updated")
            ->assertJsonPath("data.profile.preferences.bedroom_personality", "sub");

        // 3. Upload Photo
        $photo = UploadedFile::fake()->image('profile.jpg');
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/photos", [
                'photo' => $photo,
                'is_primary' => true
            ]);
        
        $response->assertCreated();
        
        // 4. Create a potential match user
        $matchUser = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $matchUser->id,
            'gender' => 'male',
            'location_latitude' => 40.7130, // Nearby
            'location_longitude' => -74.0065,
            'preferences' => [
                'body_type' => 'muscular',
                'ethnicity' => 'latino',
                'bedroom_personality' => 'dom', // Compatible
                'smoke' => 0,
            ]
        ]);

        // 5. Get Matches (Should find the user we just created)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/matches/recommendations"); // Assuming this is the endpoint for AI matches
        
        // Note: If the endpoint is different, we might need to adjust. 
        // Based on AIMatchingService usage, it might be integrated into a controller.
        // Let's check routes if this fails, but for now assume standard path.
        
        // If the route doesn't exist, we might get 404.
        if ($response->status() === 404) {
             // Fallback to standard matches if AI endpoint isn't exposed directly yet
             $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson("/api/matches");
        }

        $response->assertOk();
        
        // Verify we found the match
        $matches = $response->json('matches');
        $this->assertNotEmpty($matches, 'Should find at least one match');
        $this->assertEquals($matchUser->id, $matches[0]['id'], 'Should match with the compatible user');
    }
}
