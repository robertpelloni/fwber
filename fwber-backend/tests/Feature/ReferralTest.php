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
            'golden_tickets_remaining' => 3
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
}
