<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_location()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'accuracy' => 10,
            'privacy_level' => 'public'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_locations', [
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public'
        ]);
    }

    public function test_user_can_get_location()
    {
        $user = User::factory()->create();
        UserLocation::create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->getJson('/api/location');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'latitude' => '40.71280000',
            'longitude' => '-74.00600000'
        ]);
    }

    public function test_user_can_update_privacy()
    {
        $user = User::factory()->create();
        UserLocation::create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->putJson('/api/location/privacy', [
            'privacy_level' => 'private'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_locations', [
            'user_id' => $user->id,
            'privacy_level' => 'private'
        ]);
    }

    public function test_user_can_clear_location()
    {
        $user = User::factory()->create();
        UserLocation::create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/location');

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_locations', [
            'user_id' => $user->id,
            'is_active' => false
        ]);
    }

    public function test_user_can_find_nearby_users()
    {
        $user = User::factory()->create();
        UserLocation::create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $nearbyUser = User::factory()->create();
        UserLocation::create([
            'user_id' => $nearbyUser->id,
            'latitude' => 40.7130, // Very close
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $farUser = User::factory()->create();
        UserLocation::create([
            'user_id' => $farUser->id,
            'latitude' => 41.7128, // Far away
            'longitude' => -74.0060,
            'privacy_level' => 'public',
            'last_updated' => now(),
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->getJson('/api/location/nearby?latitude=40.7128&longitude=-74.0060&radius=1000');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $nearbyUser->id]);
        $response->assertJsonMissing(['id' => $farUser->id]);
    }
}
