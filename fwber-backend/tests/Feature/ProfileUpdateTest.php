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
}
