<?php

namespace Tests\Feature;

use App\Models\MerchantInventory;
use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantTrustModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_review_merchants_and_dashboard_counts_pending_queue(): void
    {
        $moderator = User::factory()->create(['role' => 'admin']);
        $merchantUser = User::factory()->create(['role' => 'merchant']);

        $merchant = MerchantProfile::create([
            'user_id' => $merchantUser->id,
            'business_name' => 'Pending Shop',
            'category' => 'retail',
            'description' => 'A new storefront awaiting review.',
            'location_name' => 'Downtown',
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'verification_status' => 'pending',
        ]);

        $this->actingAs($moderator)
            ->getJson('/api/moderation/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.pending_merchants', 1);

        $this->actingAs($moderator)
            ->getJson('/api/moderation/merchants')
            ->assertOk()
            ->assertJsonPath('data.0.business_name', 'Pending Shop')
            ->assertJsonPath('data.0.trust_tier', 'new');

        $this->actingAs($moderator)
            ->patchJson("/api/moderation/merchants/{$merchant->id}", [
                'verification_status' => 'verified',
                'verification_notes' => 'Merchant identity confirmed by moderator.',
            ])
            ->assertOk()
            ->assertJsonPath('merchant.verification_status', 'verified');

        $this->assertDatabaseHas('merchant_profiles', [
            'id' => $merchant->id,
            'verification_status' => 'verified',
            'verified_by' => $moderator->id,
        ]);
    }

    public function test_nearby_marketplace_ranking_can_prefer_verified_trusted_merchant_over_slightly_closer_pending_one(): void
    {
        $pendingMerchantUser = User::factory()->create(['role' => 'merchant']);
        $verifiedMerchantUser = User::factory()->create(['role' => 'merchant']);

        $pendingMerchant = MerchantProfile::create([
            'user_id' => $pendingMerchantUser->id,
            'business_name' => 'Closer Pending Shop',
            'category' => 'retail',
            'description' => 'Still waiting for review.',
            'address' => '100 Close St',
            'location_name' => 'Near Block',
            'latitude' => 42.3315,
            'longitude' => -83.0457,
            'verification_status' => 'pending',
        ]);

        $verifiedMerchant = MerchantProfile::create([
            'user_id' => $verifiedMerchantUser->id,
            'business_name' => 'Verified Trusted Shop',
            'category' => 'retail',
            'description' => 'A trusted, reviewed storefront.',
            'address' => '200 Trust Ave',
            'location_name' => 'Trust District',
            'latitude' => 42.3320,
            'longitude' => -83.0459,
            'verification_status' => 'verified',
            'verified_by' => User::factory()->create(['role' => 'admin'])->id,
            'verified_at' => now(),
        ]);

        $pendingItem = MerchantInventory::create([
            'merchant_profile_id' => $pendingMerchant->id,
            'name' => 'Pending Item',
            'price_usd' => 10,
            'stock_count' => 5,
            'is_available' => true,
        ]);

        $verifiedItem = MerchantInventory::create([
            'merchant_profile_id' => $verifiedMerchant->id,
            'name' => 'Verified Item',
            'price_usd' => 12,
            'stock_count' => 5,
            'is_available' => true,
        ]);

        MerchantPayment::create([
            'merchant_profile_id' => $verifiedMerchant->id,
            'merchant_inventory_id' => $verifiedItem->id,
            'amount' => 12,
            'currency' => 'USD',
            'payment_gateway' => 'mock',
            'transaction_id' => 'trusted_order_1',
            'status' => 'succeeded',
            'description' => 'Verified merchant proof order',
            'paid_at' => now(),
        ]);

        $response = $this->getJson('/api/marketplace/nearby?lat=42.3314&lng=-83.0458&radius=1000');
        $response->assertOk();
        $response->assertJsonPath('items.0.name', 'Verified Item');
        $response->assertJsonPath('items.0.merchant.business_name', 'Verified Trusted Shop');
        $response->assertJsonPath('items.0.merchant.verification_status', 'verified');
        $response->assertJsonPath('items.0.trust_score', fn ($score) => is_numeric($score) && $score > 50);
    }
}
