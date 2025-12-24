<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumMatchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_matches_by_cannabis_status()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'cannabis_status' => 'regular',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'cannabis_status' => 'non-smoker',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?cannabis=regular');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_diet()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'dietary_preferences' => 'vegan',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'dietary_preferences' => 'omnivore',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?diet=vegan');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_politics()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'political_views' => 'liberal',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'political_views' => 'conservative',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?politics=liberal');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_has_pets()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'has_pets' => true,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'has_pets' => false,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?has_pets=yes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_has_children()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'has_children' => true,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'has_children' => false,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?has_children=yes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }
}
