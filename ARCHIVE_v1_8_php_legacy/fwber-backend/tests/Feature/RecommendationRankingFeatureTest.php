<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecommendationRankingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_responses_expose_ranking_strategy_metadata(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Sanctum::actingAs($user);

        $recommendationsResponse = $this->getJson('/api/recommendations');
        $recommendationsResponse->assertOk()
            ->assertJsonPath('metadata.ranking_strategy.trusted_connections', true)
            ->assertJsonPath('metadata.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('metadata.ranking_strategy.freshness', true)
            ->assertJsonPath('metadata.ranking_strategy.base_relevance', true);

        $feedResponse = $this->getJson('/api/recommendations/feed');
        $feedResponse->assertOk()
            ->assertJsonPath('metadata.ranking_strategy.trusted_connections', true)
            ->assertJsonPath('metadata.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('metadata.ranking_strategy.freshness', true)
            ->assertJsonPath('metadata.ranking_strategy.base_relevance', true);
    }
}
