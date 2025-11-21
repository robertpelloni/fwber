<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserLocation;
use App\Models\ProximityArtifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ProximityFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable feature flags for proximity
        Config::set('features.proximity_artifacts', true);
        Config::set('features.proximity_chatrooms', true);
    }

    public function test_user_can_update_location()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'accuracy' => 10,
            'privacy_level' => 'public',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_locations', [
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    }

    public function test_user_can_find_nearby_users()
    {
        // Create main user
        $user = User::factory()->create();
        
        // Set user location
        UserLocation::create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'is_active' => true,
            'last_updated' => now(),
        ]);

        // Create nearby user
        $nearbyUser = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $nearbyUser->id]);
        UserLocation::create([
            'user_id' => $nearbyUser->id,
            'latitude' => 40.7130, // Very close
            'longitude' => -74.0062,
            'privacy_level' => 'public',
            'is_active' => true,
            'last_updated' => now(),
        ]);

        // Create far user
        $farUser = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $farUser->id]);
        UserLocation::create([
            'user_id' => $farUser->id,
            'latitude' => 41.7128, // Far away
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'is_active' => true,
            'last_updated' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/location/nearby?latitude=40.7128&longitude=-74.0060&radius=1000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nearbyUser->id);
    }

    public function test_user_can_create_proximity_artifact()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/proximity/artifacts', [
            'type' => 'announce',
            'content' => 'Hello nearby world!',
            'lat' => 40.7128,
            'lng' => -74.0060,
            'radius' => 500,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('artifact.content', 'Hello nearby world!');

        $this->assertDatabaseHas('proximity_artifacts', [
            'user_id' => $user->id,
            'content' => 'Hello nearby world!',
            'type' => 'announce',
        ]);
    }

    public function test_user_can_fetch_local_pulse()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // Create an artifact nearby
        ProximityArtifact::create([
            'user_id' => User::factory()->create()->id,
            'type' => 'announce',
            'content' => 'Nearby artifact',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'visibility_radius_m' => 1000,
            'expires_at' => now()->addHour(),
            'moderation_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/proximity/local-pulse?lat=40.7128&lng=-74.0060&radius=1000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'artifacts',
                'candidates',
                'meta'
            ]);
    }
}
