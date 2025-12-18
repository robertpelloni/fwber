<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_travel_mode_settings()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_travel_mode' => false,
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'is_travel_mode' => true,
            'travel_location' => [
                'name' => 'Paris, France',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'is_travel_mode' => true,
            'travel_location_name' => 'Paris, France',
            'travel_latitude' => 48.8566,
            'travel_longitude' => 2.3522,
        ]);
    }

    public function test_can_disable_travel_mode()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_travel_mode' => true,
            'travel_location_name' => 'Paris, France',
            'travel_latitude' => 48.8566,
            'travel_longitude' => 2.3522,
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'is_travel_mode' => false,
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'is_travel_mode' => false,
            // Fields should remain but flag is false
            'travel_location_name' => 'Paris, France',
        ]);
    }

    public function test_basic_profile_update_with_birthdate()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->putJson('/api/profile', [
            'display_name' => 'Updated Name',
            'birthdate' => '1995-05-05',
            'gender' => 'male',
            'looking_for' => ['dating'],
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'city' => 'New York',
                'state' => 'NY'
            ]
        ]);

        $response->dump();
        
        $response->assertStatus(200);
    }

    public function test_onboarding_profile_update()
    {
        $user = User::factory()->create();
        
        // Mimic the payload from frontend
        $payload = [
            'display_name' => 'New User',
            'birthdate' => '1990-01-01',
            'gender' => 'female',
            'location' => [
                'city' => 'San Francisco',
                'state' => 'CA',
                // latitude and longitude might be missing if user didn't use geolocation
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'New User',
            'location_name' => 'San Francisco, CA',
        ]);
    }

    public function test_profile_update_with_empty_birthdate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'birthdate' => '',
        ];

        $response = $this->putJson('/api/profile', $payload);

        $response->assertStatus(200);
    }
}
