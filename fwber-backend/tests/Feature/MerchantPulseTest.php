<?php

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MerchantPulseTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_vibe_uses_latest_promotion_location_when_request_coordinates_are_missing(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
        ]);

        $profile = MerchantProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'lat' => 42.3314,
            'lng' => -83.0458,
            'radius' => 250,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/merchant/pulse/vibe');

        $response
            ->assertOk()
            ->assertJsonPath('location.lat', '42.33140000')
            ->assertJsonPath('location.lng', '-83.04580000')
            ->assertJsonPath('location_source', 'latest_promotion')
            ->assertJsonStructure([
                'business_name',
                'location' => ['lat', 'lng', 'radius'],
                'analysis' => ['vibe', 'sentiment', 'trending_keywords', 'activity_score', 'summary'],
            ]);
    }

    public function test_merchant_vibe_requires_location_when_no_promotion_coordinates_exist(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
        ]);

        MerchantProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/merchant/pulse/vibe');

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'Location coordinates required');
    }
}
