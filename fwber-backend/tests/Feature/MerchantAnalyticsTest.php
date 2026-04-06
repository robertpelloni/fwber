<?php

namespace Tests\Feature;

use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\ProximityArtifact;
use App\Models\User;
use App\Services\MerchantAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchantAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private MerchantAnalyticsService $service;

    private User $merchantUser;

    private MerchantProfile $merchantProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MerchantAnalyticsService;

        $this->merchantUser = User::factory()->create();
        $this->merchantProfile = MerchantProfile::factory()->create([
            'user_id' => $this->merchantUser->id,
        ]);
    }

    public function test_it_calculates_kpis_correctly()
    {
        // Seeding Revenue
        MerchantPayment::factory()->create([
            'merchant_id' => $this->merchantUser->id,
            'amount' => 100,
            'status' => 'paid',
            'created_at' => Carbon::now(),
        ]);

        $kpis = $this->service->getKPIs($this->merchantProfile, '7d');

        $this->assertEquals(100, $kpis['totalRevenue']);
        $this->assertArrayHasKey('kFactor', $kpis);
        $this->assertArrayHasKey('totalReach', $kpis);
        $this->assertArrayHasKey('conversionRate', $kpis);
        $this->assertArrayHasKey('revenueChange', $kpis);
    }

    public function test_it_returns_retention_data()
    {
        $retention = $this->service->getRetention($this->merchantProfile, '7d');

        $this->assertIsArray($retention);
        $this->assertCount(4, $retention);
        $this->assertEquals('Day 1', $retention[0]['label']);
    }

    public function test_it_returns_promotions_performance()
    {
        Promotion::factory()->create([
            'merchant_id' => $this->merchantProfile->id,
            'title' => 'Test Promo',
            'created_at' => Carbon::now(),
        ]);

        $performance = $this->service->getPromotionsPerformance($this->merchantProfile, '7d');

        $this->assertCount(1, $performance);
        $this->assertEquals('Test Promo', $performance[0]['title']);
        $this->assertArrayHasKey('views', $performance[0]);
        $this->assertArrayHasKey('clicks', $performance[0]);
        $this->assertArrayHasKey('redemptions', $performance[0]);
        $this->assertArrayHasKey('revenue', $performance[0]);
        $this->assertArrayHasKey('conversionRate', $performance[0]);
    }

    public function test_it_returns_recent_merchant_broadcast_history()
    {
        $promotion = Promotion::factory()->create([
            'merchant_id' => $this->merchantProfile->id,
            'title' => 'Late Night Happy Hour',
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $this->merchantUser->id,
            'type' => 'announce',
            'content' => 'Two-for-one cocktails until midnight.',
            'visibility_radius_m' => 1609,
            'expires_at' => Carbon::now()->addHour(),
            'meta' => [
                'source' => 'merchant_pulse_broadcast',
                'merchant_profile_id' => $this->merchantProfile->id,
                'promotion_id' => $promotion->id,
                'promo_code' => 'NIGHTCAP',
                'vibe_target' => 'energetic',
                'vibe_snapshot' => 'Energetic',
                'activity_score' => 19,
            ],
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $this->merchantUser->id,
            'type' => 'announce',
            'content' => 'General neighborhood chatter.',
            'meta' => [
                'source' => 'user_post',
                'merchant_profile_id' => $this->merchantProfile->id,
            ],
        ]);

        $history = $this->service->getBroadcastHistory($this->merchantProfile, '7d');

        $this->assertCount(1, $history);
        $this->assertSame('Two-for-one cocktails until midnight.', $history[0]['content']);
        $this->assertSame('Late Night Happy Hour', $history[0]['promotion_title']);
        $this->assertSame('NIGHTCAP', $history[0]['promo_code']);
        $this->assertSame('active', $history[0]['status']);
    }

    public function test_analytics_endpoint_includes_broadcast_history()
    {
        $promotion = Promotion::factory()->create([
            'merchant_id' => $this->merchantProfile->id,
            'title' => 'Dance Floor Drop',
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $this->merchantUser->id,
            'type' => 'announce',
            'content' => 'The DJ set starts now.',
            'meta' => [
                'source' => 'merchant_pulse_broadcast',
                'merchant_profile_id' => $this->merchantProfile->id,
                'promotion_id' => $promotion->id,
                'vibe_target' => 'any',
                'vibe_snapshot' => 'Energetic',
            ],
        ]);

        Sanctum::actingAs($this->merchantUser);

        $response = $this->getJson('/api/merchant-portal/analytics');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'broadcasts')
            ->assertJsonPath('broadcasts.0.content', 'The DJ set starts now.')
            ->assertJsonPath('broadcasts.0.promotion_title', 'Dance Floor Drop');
    }
}
