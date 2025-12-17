<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingProfileUpdateTest extends TestCase
{
    use RefreshDatabase; // Don't use RefreshDatabase on production/shared DB

    public function test_onboarding_basics_step()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        $payload = [
            'display_name' => 'Test User',
            'birthdate' => '1990-01-01',
            'gender' => 'non-binary',
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'city' => 'New York',
                'state' => 'NY'
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Test User',
        ]);
    }

    public function test_onboarding_preferences_step()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create(['user_id' => $user->id]);

        // Frontend sends "Friends" (lowercase "friends" in value), Backend expects "friendship"
        // We fixed backend to accept "friends"
        $payload = [
            'looking_for' => ['dating', 'friends'], // "friends" should be mapped or accepted
            'preferences' => [
                'age_range_min' => 21,
                'age_range_max' => 45,
            ]
        ];

        $response = $this->actingAs($user)->putJson('/api/profile', $payload);

        $response->assertStatus(200);

        $profile->refresh();
        // Check if it was saved correctly
        // Note: looking_for is cast to array
        // If looking_for is null (because column missing), this will fail
        if (is_array($profile->looking_for)) {
            $this->assertTrue(in_array('dating', $profile->looking_for));
            $this->assertTrue(in_array('friends', $profile->looking_for) || in_array('friendship', $profile->looking_for));
        } else {
            dump('looking_for is not an array: ' . var_export($profile->looking_for, true));
            $this->fail('looking_for is not an array');
        }
        
        $this->assertEquals(21, $profile->preferences['age_range_min']);
        $this->assertEquals(45, $profile->preferences['age_range_max']);
    }
}
