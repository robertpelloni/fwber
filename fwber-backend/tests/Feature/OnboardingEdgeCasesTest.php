<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingEdgeCasesTest extends TestCase
{
    use RefreshDatabase; 

    public function test_update_with_empty_location_object()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'location' => [] // Empty object
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);
    }

    public function test_update_with_partial_location_object()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'location' => [
                'city' => 'New York',
                // Missing latitude/longitude/state
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);
        
        $response->assertJson([
            'data' => [
                'profile' => [
                    'location' => [
                        'city' => 'New York',
                        'state' => null
                    ]
                ]
            ]
        ]);
    }

    public function test_update_with_zero_coordinates()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'location' => [
                'latitude' => 0,
                'longitude' => 0,
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);
    }

    public function test_update_with_empty_strings_in_location()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'location' => [
                'city' => '',
                'state' => '',
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        // This might fail if empty strings are not allowed for city/state
        // But they are nullable, so maybe?
        // Rule: 'location.city' => 'sometimes|nullable|string|max:100'
        // Empty string is a string.
        $response->assertStatus(200);
    }

    public function test_update_with_underage_birthdate()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => now()->subYears(17)->toDateString(), // 17 years old
            'gender' => 'male',
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['birthdate']);
    }
}
