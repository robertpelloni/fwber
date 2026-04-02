<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DealRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_deals_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee'],
        ]);

        $merchantUser = User::factory()->create(['role' => 'merchant']);
        $merchant = MerchantProfile::factory()->create([
            'user_id' => $merchantUser->id,
            'verification_status' => 'verified',
            'business_name' => 'Morning Roast',
            'category' => 'cafe',
        ]);

        Promotion::factory()->create([
            'merchant_id' => $merchant->id,
            'title' => 'Morning coffee special',
            'lat' => 42.3314,
            'lng' => -83.0458,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/deals?lat=42.3314&lng=-83.0458&radius=2000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_merchants', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.deal_health', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'deals');
    }

    public function test_trusted_scene_aligned_deal_can_outrank_a_closer_generic_deal(): void
    {
        $viewer = User::factory()->create();
        $friendMerchantUser = User::factory()->create(['role' => 'merchant']);
        $strangerMerchantUser = User::factory()->create(['role' => 'merchant']);

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'interests' => ['coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create(['user_id' => $friendMerchantUser->id]);
        UserProfile::factory()->create(['user_id' => $strangerMerchantUser->id]);

        Friend::factory()->create([
            'user_id' => $viewer->id,
            'friend_id' => $friendMerchantUser->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friendMerchantUser->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        $friendMerchant = MerchantProfile::factory()->create([
            'user_id' => $friendMerchantUser->id,
            'business_name' => 'Warehouse Coffee Collective',
            'category' => 'cafe',
            'description' => 'Coffee before the warehouse nightlife starts.',
            'verification_status' => 'verified',
        ]);
        $strangerMerchant = MerchantProfile::factory()->create([
            'user_id' => $strangerMerchantUser->id,
            'business_name' => 'Generic Nearby Store',
            'category' => 'retail',
            'description' => 'General local discount.',
            'verification_status' => 'pending',
        ]);

        Promotion::factory()->create([
            'merchant_id' => $strangerMerchant->id,
            'title' => 'Generic nearby discount',
            'description' => 'Everyday local savings.',
            'discount_value' => '10',
            'lat' => 42.33145,
            'lng' => -83.04575,
            'views' => 1,
            'clicks' => 0,
            'redemptions' => 0,
            'created_at' => now()->subHours(8),
        ]);
        Promotion::factory()->create([
            'merchant_id' => $friendMerchant->id,
            'title' => 'Warehouse coffee pre-game',
            'description' => 'Coffee and nightlife perk before the party.',
            'discount_value' => '25',
            'lat' => 42.3328,
            'lng' => -83.0474,
            'views' => 40,
            'clicks' => 12,
            'redemptions' => 4,
            'created_at' => now()->subMinutes(30),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/deals?lat=42.3314&lng=-83.0458&radius=2000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('deals.0.title', 'Warehouse coffee pre-game')
            ->assertJsonPath('deals.0.scene_signals.matched_tags.0', 'coffee');
    }
}
