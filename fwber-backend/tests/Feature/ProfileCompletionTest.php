<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function incomplete_profile_blocked_from_matches()
    {
        // Create user without profile
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Attempt to access matches without profile
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Profile not found. Please complete your profile to continue.',
        ]);
        $response->assertJsonStructure([
            'message',
            'required_fields',
        ]);
    }

    #[Test]
    public function profile_missing_display_name_blocked()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create profile without display_name
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => null,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'New York, NY',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Please complete your profile before accessing this feature.',
        ]);
        $response->assertJsonFragment(['field' => 'display_name']);
    }

    #[Test]
    public function profile_missing_date_of_birth_blocked()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create profile without date_of_birth
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'TestUser',
            'date_of_birth' => null,
            'gender' => 'male',
            'location_description' => 'New York, NY',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJsonFragment(['field' => 'date_of_birth']);
    }

    #[Test]
    public function profile_missing_gender_blocked()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create profile without gender
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'TestUser',
            'date_of_birth' => '1990-01-01',
            'gender' => null,
            'location_description' => 'New York, NY',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJsonFragment(['field' => 'gender']);
    }

    #[Test]
    public function profile_missing_location_blocked()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create profile without location
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'TestUser',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => null,
            'location_latitude' => null,
            'location_longitude' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJsonFragment(['field' => 'location']);
    }

    #[Test]
    public function complete_profile_with_description_allowed()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create complete profile with location description
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'TestUser',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'New York, NY',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        // Should pass middleware (may return empty matches or 404 for no MatchController implementation)
        // But NOT 400 from profile gate
        $this->assertNotEquals(400, $response->status());
    }

    #[Test]
    public function complete_profile_with_coordinates_allowed()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create complete profile with coordinates
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'TestUser',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        // Should pass middleware
        $this->assertNotEquals(400, $response->status());
    }

    #[Test]
    public function match_action_requires_complete_profile()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // No profile
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/matches/action', [
                'target_user_id' => 999,
                'action' => 'like',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Profile not found. Please complete your profile to continue.',
        ]);
    }

    #[Test]
    public function middleware_returns_helpful_missing_fields_list()
    {
        $user = User::factory()->create();
        $token = ApiToken::generateForUser($user, 'test');

        // Create profile missing multiple fields
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => null,
            'date_of_birth' => null,
            'gender' => 'male',
            'location_description' => 'New York, NY',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches');

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'message',
            'missing_fields' => [
                '*' => ['field', 'label']
            ],
        ]);
        
        $missingFields = $response->json('missing_fields');
        $this->assertGreaterThanOrEqual(2, count($missingFields));
    }
}
