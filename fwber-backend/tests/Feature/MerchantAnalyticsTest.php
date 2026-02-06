<?php

namespace Tests\Feature;

use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\User;
use App\Services\MerchantAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->service = new MerchantAnalyticsService();
        
        $this->merchantUser = User::factory()->create();
        $this->merchantProfile = MerchantProfile::factory()->create([
            'user_id' => $this->merchantUser->id
        ]);
    }

    public function test_it_calculates_kpis_correctly()
    {
        // Seeding Revenue
        MerchantPayment::factory()->create([
            'merchant_id' => $this->merchantUser->id,
            'amount' => 100,
            'status' => 'completed',
            'created_at' => Carbon::now()
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
            'created_at' => Carbon::now()
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
}
