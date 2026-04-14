<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckDailyBonus;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\ProximityArtifact;
use App\Models\User;
use App\Services\LocalPulseAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MerchantPulseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckDailyBonus::class);
    }

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

    public function test_broadcast_creates_merchant_announce_and_spends_tokens_when_vibe_matches(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
            'token_balance' => 125,
        ]);

        $profile = MerchantProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Pulse Lounge',
        ]);

        $promotion = Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'lat' => 42.3314,
            'lng' => -83.0458,
            'radius' => 1609,
        ]);

        $mock = Mockery::mock(LocalPulseAnalyticsService::class);
        $mock->shouldReceive('getNeighborhoodVibe')
            ->once()
            ->with(42.3314, -83.0458, 1609)
            ->andReturn([
                'vibe' => 'Energetic',
                'sentiment' => 0.82,
                'trending_keywords' => ['happy-hour'],
                'activity_score' => 18,
                'summary' => 'Downtown is lively tonight.',
                'post_count' => 9,
            ]);
        $this->instance(LocalPulseAnalyticsService::class, $mock);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/merchant/pulse/broadcast', [
            'content' => 'Happy hour is live right now. Show your fwber badge for 2-for-1 cocktails.',
            'discount_code' => 'PULSE20',
            'vibe_target' => 'energetic',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'broadcast_sent')
            ->assertJsonPath('token_cost', 50)
            ->assertJsonPath('remaining_balance', '75.0000')
            ->assertJsonPath('current_vibe', 'Energetic')
            ->assertJsonPath('location_source', 'latest_promotion');

        $artifact = ProximityArtifact::first();
        $this->assertNotNull($artifact);
        $this->assertSame('announce', $artifact->type);
        $this->assertSame($user->id, $artifact->user_id);
        $this->assertSame($promotion->id, $artifact->meta['promotion_id']);
        $this->assertSame('merchant_pulse_broadcast', $artifact->meta['source']);
        $this->assertSame('PULSE20', $artifact->meta['promo_code']);
        $this->assertSame('energetic', $artifact->meta['vibe_target']);
        $this->assertSame('Energetic', $artifact->meta['vibe_snapshot']);

        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => '-50.0000',
            'description' => 'Merchant vibe broadcast for Pulse Lounge',
        ]);
    }

    public function test_broadcast_rejects_when_live_vibe_does_not_match_target_without_spending_tokens(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
            'token_balance' => 125,
        ]);

        $profile = MerchantProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Pulse Lounge',
        ]);

        Promotion::factory()->create([
            'merchant_id' => $profile->id,
            'lat' => 42.3314,
            'lng' => -83.0458,
            'radius' => 1609,
        ]);

        $mock = Mockery::mock(LocalPulseAnalyticsService::class);
        $mock->shouldReceive('getNeighborhoodVibe')
            ->once()
            ->andReturn([
                'vibe' => 'Quiet',
                'sentiment' => 0.5,
                'trending_keywords' => [],
                'activity_score' => 0,
                'summary' => 'Not enough data for this area yet.',
                'post_count' => 0,
            ]);
        $this->instance(LocalPulseAnalyticsService::class, $mock);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/merchant/pulse/broadcast', [
            'content' => 'Date night special is ready.',
            'vibe_target' => 'romantic',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('status', 'blocked_vibe_mismatch')
            ->assertJsonPath('current_vibe', 'Quiet')
            ->assertJsonPath('vibe_target', 'romantic');

        $this->assertDatabaseCount('proximity_artifacts', 0);
        $this->assertDatabaseCount('token_transactions', 0);
        $this->assertSame('125.0000', $user->fresh()->token_balance);
    }

    public function test_broadcast_requires_promotion_location_before_sending(): void
    {
        $user = User::factory()->create([
            'role' => 'merchant',
            'token_balance' => 125,
        ]);

        MerchantProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/merchant/pulse/broadcast', [
            'content' => 'Flash special ready now.',
            'vibe_target' => 'any',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'Location coordinates required');

        $this->assertDatabaseCount('proximity_artifacts', 0);
        $this->assertDatabaseCount('token_transactions', 0);
    }
}
