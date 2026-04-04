<?php

namespace Tests\Feature;

use App\Models\MerchantInventory;
use App\Models\MerchantProfile;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MerchantRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_merchant_and_inherit_profile_location(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'location_name' => 'Downtown Detroit',
        ]);

        $this->actingAs($user)
            ->postJson('/api/merchant-portal/register', [
                'business_name' => "Joe's Pizza",
                'category' => 'restaurant',
                'description' => 'Late night slices.',
                'address' => '123 Main St',
            ])
            ->assertOk()
            ->assertJsonPath('profile.business_name', "Joe's Pizza")
            ->assertJsonPath('profile.location_name', 'Downtown Detroit')
            ->assertJsonPath('profile.latitude', 42.3314)
            ->assertJsonPath('profile.longitude', -83.0458);

        $user->refresh();
        $this->assertSame('merchant', $user->role);
        $this->assertDatabaseHas('merchant_profiles', [
            'user_id' => $user->id,
            'business_name' => "Joe's Pizza",
            'location_name' => 'Downtown Detroit',
        ]);

        $this->actingAs($user)
            ->getJson('/api/merchant-portal/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.inventory_count', 0)
            ->assertJsonPath('profile.latitude', 42.3314);
    }

    public function test_nearby_marketplace_endpoint_sorts_by_distance_when_coordinates_are_provided(): void
    {
        $nearMerchantUser = User::factory()->create(['role' => 'merchant']);
        $farMerchantUser = User::factory()->create(['role' => 'merchant']);

        $nearMerchant = MerchantProfile::create([
            'user_id' => $nearMerchantUser->id,
            'business_name' => 'Near Shop',
            'category' => 'retail',
            'location_name' => 'Central',
            'latitude' => 42.3315,
            'longitude' => -83.0457,
            'verification_status' => 'pending',
        ]);

        $farMerchant = MerchantProfile::create([
            'user_id' => $farMerchantUser->id,
            'business_name' => 'Far Shop',
            'category' => 'retail',
            'location_name' => 'Far Away',
            'latitude' => 42.3600,
            'longitude' => -83.1000,
            'verification_status' => 'pending',
        ]);

        MerchantInventory::create([
            'merchant_profile_id' => $farMerchant->id,
            'name' => 'Far Item',
            'price_usd' => 10,
            'stock_count' => 5,
            'is_available' => true,
        ]);

        MerchantInventory::create([
            'merchant_profile_id' => $nearMerchant->id,
            'name' => 'Near Item',
            'price_usd' => 10,
            'stock_count' => 5,
            'is_available' => true,
        ]);

        $response = $this->getJson('/api/marketplace/nearby?lat=42.3314&lng=-83.0458&radius=10000');
        $response->assertOk();
        $response->assertJsonPath('items.0.name', 'Near Item');
        $response->assertJsonPath('items.0.merchant.business_name', 'Near Shop');
        $response->assertJsonPath('items.0.distance_m', fn ($distance) => is_numeric($distance) && $distance < 1000);
    }

    public function test_merchant_can_manage_inventory_and_redeem_purchase(): void
    {
        $merchantUser = User::factory()->create(['role' => 'merchant']);
        $buyer = User::factory()->create();
        $merchantProfile = MerchantProfile::create([
            'user_id' => $merchantUser->id,
            'business_name' => 'The Warehouse',
            'category' => 'retail',
            'location_name' => 'Midtown',
            'latitude' => 42.3410,
            'longitude' => -83.0550,
            'verification_status' => 'pending',
        ]);

        $createResponse = $this->actingAs($merchantUser)->postJson('/api/merchant-portal/inventory', [
            'name' => 'VIP Wristband',
            'description' => 'Skip the queue.',
            'price_usd' => 15,
            'stock_count' => 5,
            'is_available' => true,
        ]);

        $createResponse->assertCreated();
        $itemId = $createResponse->json('item.id');

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                transactionId: 'mp_mock_123',
                message: 'Marketplace purchase succeeded',
                data: ['status' => 'succeeded']
            ));
        $this->app->instance(PaymentGatewayInterface::class, $gateway);
        config()->set('services.payment.driver', 'mock');

        $purchaseResponse = $this->actingAs($buyer)->postJson("/api/marketplace/purchase/{$itemId}", []);
        $purchaseResponse->assertOk();
        $purchaseResponse->assertJsonPath('merchant_name', 'The Warehouse');

        $code = $purchaseResponse->json('redemption_code');
        $this->assertNotEmpty($code);

        $item = MerchantInventory::findOrFail($itemId);
        $this->assertSame(4, $item->stock_count);

        $this->actingAs($merchantUser)
            ->postJson('/api/merchant-portal/inventory/redeem', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('item_name', 'VIP Wristband');

        $this->actingAs($merchantUser)
            ->getJson('/api/merchant-portal/analytics')
            ->assertOk()
            ->assertJsonPath('summary.orders', 1)
            ->assertJsonPath('summary.redeemed_redemptions', 1);

        $this->assertDatabaseHas('merchant_payments', [
            'merchant_profile_id' => $merchantProfile->id,
            'payer_id' => $buyer->id,
            'transaction_id' => 'mp_mock_123',
            'status' => 'succeeded',
        ]);
    }
}
