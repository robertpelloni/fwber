<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_merchant_cannot_create_promotion()
    {
        $user = User::factory()->create(['role' => 'merchant']);
        $merchant = MerchantProfile::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending' // Override factory default
        ]);

        $response = $this->actingAs($user)->postJson('/api/merchant-portal/promotions', [
            'title' => 'Test Promo',
            'description' => 'Test Description',
            'discount_value' => '10%',
            'lat' => 40.7128,
            'lng' => -74.0060,
            'radius' => 1000,
            'starts_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Merchant profile must be verified to create promotions.']);
    }

    public function test_verified_merchant_can_create_promotion()
    {
        $user = User::factory()->create(['role' => 'merchant']);
        $merchant = MerchantProfile::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'verified'
        ]);

        $response = $this->actingAs($user)->postJson('/api/merchant-portal/promotions', [
            'title' => 'Test Promo',
            'description' => 'Test Description',
            'discount_value' => '10%',
            'lat' => 40.7128,
            'lng' => -74.0060,
            'radius' => 1000,
            'starts_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response->assertStatus(201);
    }
}
