<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReferralRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_referral_lookup_and_vouch_routes_are_restored(): void
    {
        $user = User::factory()->create([
            'name' => 'Referrer',
            'referral_code' => 'REF1000',
            'golden_tickets_remaining' => 1,
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

        $this->getJson('/api/auth/referral/REF1000')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'referrer_name' => 'Referrer',
                'referrer_avatar' => 'https://example.com/avatar.png',
                'has_golden_tickets' => true,
            ]);

        $this->actingAs($user)
            ->getJson('/api/vouch/link')
            ->assertOk()
            ->assertJsonPath('url', rtrim((string) config('referrals.frontend_url', 'https://fwber.me'), '/').'/vouch/REF1000');

        $this->postJson('/api/public/vouch', [
            'referral_code' => 'REF1000',
            'type' => 'safe',
            'relationship_type' => 'friend',
            'comment' => 'Solid human.',
            'voucher_name' => 'Wing Friend',
        ])->assertOk();

        $this->assertDatabaseHas('vouches', [
            'to_user_id' => $user->id,
            'type' => 'safe',
        ]);
    }

    public function test_registering_with_referral_code_links_referrer_and_awards_signup_rewards(): void
    {
        $referrer = User::factory()->create([
            'name' => 'Referrer',
            'referral_code' => 'REF2000',
            'token_balance' => 0,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Person',
            'email' => 'new-person@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'referral_code' => 'REF2000',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.referrals_count', 0);

        $referredUser = User::query()->where('email', 'new-person@example.com')->firstOrFail();
        $referrer->refresh();

        $this->assertSame($referrer->id, $referredUser->referrer_id);
        $this->assertSame('50.00', number_format((float) $referrer->token_balance, 2, '.', ''));
        $this->assertSame('50.00', number_format((float) $referredUser->token_balance, 2, '.', ''));

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $referrer->id,
            'type' => 'referral_signup_bonus',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $referredUser->id,
            'type' => 'referred_signup_bonus',
        ]);
    }

    public function test_premium_purchase_creates_multi_level_referral_commissions_and_summary(): void
    {
        $root = User::factory()->create([
            'name' => 'Root Referrer',
            'referral_code' => 'ROOT100',
        ]);

        $direct = User::factory()->create([
            'name' => 'Direct Referrer',
            'referral_code' => 'DIRECT1',
            'referrer_id' => $root->id,
        ]);

        $purchaser = User::factory()->create([
            'name' => 'Purchaser',
            'referral_code' => 'BUYER01',
            'referrer_id' => $direct->id,
        ]);

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                transactionId: 'ch_mock_referral_123',
                message: 'Payment successful',
                data: ['status' => 'succeeded']
            ));

        $this->app->instance(PaymentGatewayInterface::class, $gateway);
        config()->set('services.payment.driver', 'mock');

        $this->actingAs($purchaser)
            ->postJson('/api/premium/purchase', [
                'plan_id' => 'gold_monthly',
            ])
            ->assertOk();

        $this->assertDatabaseHas('referral_commissions', [
            'beneficiary_user_id' => $direct->id,
            'level' => 1,
            'cash_amount' => '2.00',
            'token_amount' => '50.00',
        ]);

        $this->assertDatabaseHas('referral_commissions', [
            'beneficiary_user_id' => $root->id,
            'level' => 2,
            'cash_amount' => '0.50',
            'token_amount' => '15.00',
        ]);

        $summary = $this->actingAs($direct)
            ->getJson('/api/referrals/summary')
            ->assertOk();

        $summary->assertJsonPath('pending_cash_usd', 2);
        $summary->assertJsonPath('earned_token_rewards', 50);
        $summary->assertJsonPath('levels.0.level', 1);
        $summary->assertJsonPath('levels.0.count', 1);
    }
}
