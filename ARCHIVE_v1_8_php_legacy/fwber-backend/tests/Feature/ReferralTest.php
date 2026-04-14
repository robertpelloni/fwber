<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_referral_stats()
    {
        $referrer = User::factory()->create([
            'referral_code' => 'TESTCODE',
            'golden_tickets_remaining' => 3,
        ]);

        $referee1 = User::factory()->create(['referrer_id' => $referrer->id]);
        $referee2 = User::factory()->create(['referrer_id' => $referrer->id]);

        $this->actingAs($referrer);

        $response = $this->getJson('/api/auth/me');

        // Laravel's loadCount adds the attribute as {relation}_count
        $response->assertStatus(200)
            ->assertJsonPath('referrals_count', 2)
            ->assertJsonPath('golden_tickets_remaining', 3);
    }

    public function test_auth_me_backfills_missing_referral_code()
    {
        $user = User::factory()->create([
            'referral_code' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertNotNull($user->fresh()->referral_code);
        $this->assertSame(8, strlen((string) $user->fresh()->referral_code));
    }

    public function test_user_can_fetch_referral_summary()
    {
        $referrer = User::factory()->create([
            'referral_code' => null,
            'golden_tickets_remaining' => 4,
            'token_balance' => 125,
        ]);

        User::factory()->count(2)->create([
            'referrer_id' => $referrer->id,
        ]);

        $response = $this->actingAs($referrer)->getJson('/api/referrals/summary');

        $response->assertOk()
            ->assertJsonPath('golden_tickets_remaining', 4)
            ->assertJsonPath('referrals_count', 2)
            ->assertJsonPath('reward_rules.level_1.cash_usd', 2)
            ->assertJsonPath('reward_rules.level_2.token_amount', 15);

        $this->assertNotNull($referrer->fresh()->referral_code);
    }
}
