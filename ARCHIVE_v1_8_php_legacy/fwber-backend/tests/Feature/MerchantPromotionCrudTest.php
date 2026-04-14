<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchantPromotionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_view_owned_promotion_detail(): void
    {
        [$merchant, $profile] = $this->createMerchant();
        $promotion = Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'views' => 24,
            'clicks' => 6,
            'redemptions' => 3,
        ]);

        Sanctum::actingAs($merchant);

        $response = $this->getJson("/api/merchant-portal/promotions/{$promotion->id}");

        $response
            ->assertOk()
            ->assertJsonPath('promotion.id', $promotion->id)
            ->assertJsonPath('metrics.views', 24)
            ->assertJsonPath('metrics.clicks', 6)
            ->assertJsonPath('metrics.redemptions', 3)
            ->assertJsonPath('metrics.conversion_rate', 12.5);
    }

    public function test_merchant_can_update_owned_promotion(): void
    {
        [$merchant, $profile] = $this->createMerchant();
        $promotion = Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'title' => 'Original Title',
            'radius' => 150,
        ]);

        Sanctum::actingAs($merchant);

        $response = $this->putJson("/api/merchant-portal/promotions/{$promotion->id}", [
            'title' => 'Late Night Special',
            'description' => 'Freshly updated happy-hour copy.',
            'radius' => 300,
            'is_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('promotion.title', 'Late Night Special')
            ->assertJsonPath('promotion.radius', 300)
            ->assertJsonPath('promotion.is_active', false);

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'title' => 'Late Night Special',
            'radius' => 300,
            'is_active' => 0,
        ]);
    }

    public function test_merchant_can_deactivate_owned_promotion(): void
    {
        [$merchant, $profile] = $this->createMerchant();
        $promotion = Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'is_active' => true,
            'expires_at' => now()->addDays(5),
        ]);

        Sanctum::actingAs($merchant);

        $response = $this->deleteJson("/api/merchant-portal/promotions/{$promotion->id}");

        $response
            ->assertOk()
            ->assertJsonPath('promotion.is_active', false);

        $promotion->refresh();

        $this->assertFalse($promotion->is_active);
        $this->assertTrue($promotion->expires_at->lte(now()->addSecond()));
    }

    public function test_merchant_cannot_manage_another_merchants_promotion(): void
    {
        [$merchant] = $this->createMerchant();
        [, $otherProfile] = $this->createMerchant();
        $promotion = Promotion::factory()->create([
            'merchant_id' => $otherProfile->id,
        ]);

        Sanctum::actingAs($merchant);

        $this->getJson("/api/merchant-portal/promotions/{$promotion->id}")
            ->assertNotFound();

        $this->putJson("/api/merchant-portal/promotions/{$promotion->id}", [
            'title' => 'Should Not Update',
        ])->assertNotFound();

        $this->deleteJson("/api/merchant-portal/promotions/{$promotion->id}")
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: MerchantProfile}
     */
    private function createMerchant(): array
    {
        $user = User::factory()->create([
            'role' => 'merchant',
        ]);

        $profile = MerchantProfile::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'verified',
        ]);

        return [$user, $profile];
    }
}
