<?php

namespace Tests\Feature;

use App\Models\ProximityArtifact;
use App\Models\User;
use App\Models\UserProfile;
use Tests\Traits\RefreshDatabaseSilently;
use Tests\TestCase;

class LocalPulseTest extends TestCase
{
    use RefreshDatabaseSilently;

    public function test_local_pulse_returns_artifacts_and_candidates(): void
    {
        // Create authenticated user with profile
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
            'preferences' => [
                'gender_preferences' => ['female' => true],
                'age_range' => ['min' => 25, 'max' => 35],
            ],
        ]);

        // Create nearby proximity artifact
        $artifact = ProximityArtifact::factory()->create([
            'type' => 'chat',
            'content' => 'Coffee meetup anyone?',
            'location_lat' => 40.7135,
            'location_lng' => -74.0065,
            'visibility_radius_m' => 1000,
            'moderation_status' => 'clean',
        ]);

        // Create nearby compatible candidate
        $candidate = User::factory()->create();
        UserProfile::factory()->for($candidate)->create([
            'location_latitude' => 40.7130,
            'location_longitude' => -74.0062,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(28),
            'preferences' => [
                'gender_preferences' => ['male' => true],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'artifacts' => [
                    '*' => ['id', 'type', 'content', 'lat', 'lng', 'expires_at'],
                ],
                'candidates' => [
                    '*' => ['user_id', 'age', 'gender', 'distance_miles', 'compatibility_indicators'],
                ],
                'meta' => ['center_lat', 'center_lng', 'radius_m', 'artifacts_count', 'candidates_count'],
            ]);

        $this->assertGreaterThan(0, count($response->json('artifacts')));
        $this->assertGreaterThan(0, count($response->json('candidates')));
    }

    public function test_local_pulse_respects_radius_filtering(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
            'preferences' => [
                'gender_preferences' => ['female' => true],
                'age_range' => ['min' => 25, 'max' => 35],
            ],
        ]);

        // Create artifact within radius
        ProximityArtifact::factory()->create([
            'location_lat' => 40.7135,
            'location_lng' => -74.0065,
            'visibility_radius_m' => 1000,
            'moderation_status' => 'clean',
        ]);

        // Create artifact far outside radius (~100 miles away)
        ProximityArtifact::factory()->create([
            'location_lat' => 42.0,
            'location_lng' => -75.0,
            'visibility_radius_m' => 1000,
            'moderation_status' => 'clean',
        ]);

        // Create candidate within radius
        $nearCandidate = User::factory()->create();
        UserProfile::factory()->for($nearCandidate)->create([
            'location_latitude' => 40.7130,
            'location_longitude' => -74.0062,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(28),
        ]);

        // Create candidate far outside radius
        $farCandidate = User::factory()->create();
        UserProfile::factory()->for($farCandidate)->create([
            'location_latitude' => 42.0,
            'location_longitude' => -75.0,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(28),
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200);

        // Should only find nearby items
        $this->assertEquals(1, count($response->json('artifacts')));
        $this->assertEquals(1, count($response->json('candidates')));

        // Verify far items not included
        $candidateIds = array_column($response->json('candidates'), 'user_id');
        $this->assertContains($nearCandidate->id, $candidateIds);
        $this->assertNotContains($farCandidate->id, $candidateIds);
    }

    public function test_local_pulse_excludes_removed_artifacts(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
        ]);

        // Create active artifact
        ProximityArtifact::factory()->create([
            'location_lat' => 40.7135,
            'location_lng' => -74.0065,
            'moderation_status' => 'clean',
        ]);

        // Create removed artifact
        ProximityArtifact::factory()->create([
            'location_lat' => 40.7135,
            'location_lng' => -74.0065,
            'moderation_status' => 'removed',
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200);
        
        // Should only show active artifact
        $this->assertEquals(1, count($response->json('artifacts')));
    }

    public function test_local_pulse_applies_gender_preference_filter(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
            'preferences' => [
                'gender_preferences' => ['female' => true, 'male' => false],
                'age_range' => ['min' => 25, 'max' => 35],
            ],
        ]);

        // Create female candidate (should be included)
        $femaleCandidate = User::factory()->create();
        UserProfile::factory()->for($femaleCandidate)->create([
            'location_latitude' => 40.7130,
            'location_longitude' => -74.0062,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(28),
        ]);

        // Create male candidate (should be excluded)
        $maleCandidate = User::factory()->create();
        UserProfile::factory()->for($maleCandidate)->create([
            'location_latitude' => 40.7130,
            'location_longitude' => -74.0062,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(28),
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200);

        $candidateIds = array_column($response->json('candidates'), 'user_id');
        $this->assertContains($femaleCandidate->id, $candidateIds);
        $this->assertNotContains($maleCandidate->id, $candidateIds);
    }

    public function test_local_pulse_requires_profile(): void
    {
        // User without profile
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(422)
            ->assertJson(['error' => 'Profile required']);
    }

    public function test_local_pulse_validates_required_params(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
        ]);

        // Missing lat/lng
        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse');
        $response->assertStatus(422);
    }

    public function test_local_pulse_limits_results(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
            'preferences' => [
                'gender_preferences' => ['female' => true],
                'age_range' => ['min' => 18, 'max' => 100],
            ],
        ]);

        // Create 25 nearby artifacts (should cap at 20)
        for ($i = 0; $i < 25; $i++) {
            ProximityArtifact::factory()->create([
                'location_lat' => 40.7130 + ($i * 0.0001),
                'location_lng' => -74.0060,
                'visibility_radius_m' => 1000,
                'moderation_status' => 'clean',
            ]);
        }

        // Create 15 nearby candidates (should cap at 10)
        for ($i = 0; $i < 15; $i++) {
            $candidate = User::factory()->create();
            UserProfile::factory()->for($candidate)->create([
                'location_latitude' => 40.7130 + ($i * 0.0001),
                'location_longitude' => -74.0060,
                'gender' => 'female',
                'date_of_birth' => now()->subYears(25 + $i),
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=2000');

        $response->assertStatus(200);

        // Verify limits applied
        $this->assertLessThanOrEqual(20, count($response->json('artifacts')));
        $this->assertLessThanOrEqual(10, count($response->json('candidates')));
    }

    public function test_local_pulse_includes_compatibility_indicators(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create([
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
            'preferences' => [
                'gender_preferences' => ['female' => true],
                'age_range' => ['min' => 25, 'max' => 35],
                'relationship_type' => ['casual' => true, 'serious' => true],
            ],
        ]);

        // Create candidate with matching relationship preferences
        $candidate = User::factory()->create();
        UserProfile::factory()->for($candidate)->create([
            'location_latitude' => 40.7130,
            'location_longitude' => -74.0062,
            'gender' => 'female',
            'date_of_birth' => now()->subYears(28),
            'preferences' => [
                'gender_preferences' => ['male' => true],
                'relationship_type' => ['casual' => true],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200);

        $candidates = $response->json('candidates');
        $this->assertNotEmpty($candidates);
        
        // Verify compatibility indicators structure
        $firstCandidate = $candidates[0];
        $this->assertArrayHasKey('compatibility_indicators', $firstCandidate);
        $this->assertIsArray($firstCandidate['compatibility_indicators']);
    }
}
