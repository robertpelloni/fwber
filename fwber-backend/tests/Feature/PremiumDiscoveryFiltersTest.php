<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumDiscoveryFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_persists_restored_discovery_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/profile', [
                'display_name' => 'Filter Ready',
                'dietary_preferences' => 'vegan',
                'religion' => 'agnostic',
                'political_views' => 'liberal',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'dietary_preferences' => 'vegan',
            'religion' => 'agnostic',
            'political_views' => 'liberal',
        ]);
    }

    public function test_token_holder_can_apply_premium_discovery_filters(): void
    {
        $user = User::factory()->create(['token_balance' => 150]);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Searcher',
            'birthdate' => now()->subYears(29)->toDateString(),
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'location_name' => 'Detroit',
        ]);

        $matchingCandidate = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $matchingCandidate->id,
            'display_name' => 'Vegan Match',
            'birthdate' => now()->subYears(28)->toDateString(),
            'latitude' => 42.3320,
            'longitude' => -83.0460,
            'location_name' => 'Detroit',
            'dietary_preferences' => 'vegan',
            'political_views' => 'liberal',
            'religion' => 'agnostic',
            'wants_children' => false,
        ]);

        $otherCandidate = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $otherCandidate->id,
            'display_name' => 'Different Vibe',
            'birthdate' => now()->subYears(28)->toDateString(),
            'latitude' => 42.3330,
            'longitude' => -83.0470,
            'location_name' => 'Detroit',
            'dietary_preferences' => 'omnivore',
            'political_views' => 'moderate',
            'religion' => 'christian',
            'wants_children' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?diet=vegan&politics=liberal&religion=agnostic&wants_children=no');

        $response->assertOk()
            ->assertJsonPath('filters.premium_unlocked', true)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $matchingCandidate->id);
    }

    public function test_non_token_holder_cannot_apply_premium_discovery_filters(): void
    {
        $user = User::factory()->create(['token_balance' => 99]);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Locked User',
            'birthdate' => now()->subYears(29)->toDateString(),
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'location_name' => 'Detroit',
        ]);

        $this->actingAs($user)
            ->getJson('/api/matches?diet=vegan')
            ->assertStatus(402)
            ->assertJsonPath('required_tokens', 100)
            ->assertJsonPath('upgrade_url', '/wallet');
    }
}
