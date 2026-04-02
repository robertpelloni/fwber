<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PremiumControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_purchase_premium_with_stripe()
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(19.99, 'USD', 'tok_visa', Mockery::on(fn ($metadata) => $metadata['plan_id'] === 'gold_monthly'
                && $metadata['plan_name'] === 'gold'
                && $metadata['stripe_price'] === 'price_premium_monthly'
                && $metadata['duration_days'] === 30
                && $metadata['description'] === 'Premium Subscription'))
            ->andReturn(new PaymentResult(true, 'ch_premium', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method_id' => 'tok_visa',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Premium purchased successfully',
                'tier' => 'gold',
            ]);

        $this->assertEquals('gold', $user->fresh()->tier);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_status' => 'active',
        ]);
    }

    public function test_can_initiate_premium_purchase_with_configured_plan_metadata()
    {
        $user = User::factory()->create();

        config([
            'premium.plans.gold_monthly.price_usd' => 24.99,
            'premium.plans.gold_monthly.duration_days' => 45,
            'premium.plans.gold_monthly.stripe_price' => 'price_gold_monthly_custom',
            'premium.plans.gold_monthly.description' => 'Gold Monthly Subscription',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->with(24.99, 'USD', Mockery::on(fn ($metadata) => $metadata['user_id'] === $user->id
                && $metadata['plan_id'] === 'gold_monthly'
                && $metadata['plan_name'] === 'gold'
                && $metadata['stripe_price'] === 'price_gold_monthly_custom'
                && $metadata['duration_days'] === 45
                && $metadata['description'] === 'Gold Monthly Subscription'))
            ->andReturn(new PaymentResult(true, 'pi_plan', null, [
                'client_secret' => 'pi_plan_secret',
                'amount' => 24.99,
                'currency' => 'USD',
            ]));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->actingAs($user)
            ->postJson('/api/premium/initiate', [
                'plan_id' => 'gold_monthly',
            ])
            ->assertOk()
            ->assertJson([
                'client_secret' => 'pi_plan_secret',
                'amount' => 24.99,
                'currency' => 'USD',
                'plan_id' => 'gold_monthly',
                'plan' => [
                    'id' => 'gold_monthly',
                    'price_usd' => 24.99,
                ],
            ]);
    }

    public function test_initiate_purchase_rejects_unknown_plan()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/premium/initiate', [
                'plan_id' => 'gold_yearly',
            ])
            ->assertStatus(422)
            ->assertJson([
                'error' => 'Unknown premium plan [gold_yearly].',
            ]);
    }

    public function test_stripe_purchase_awards_two_level_referral_commissions()
    {
        $levelTwo = User::factory()->create();
        $levelOne = User::factory()->create(['referrer_id' => $levelTwo->id]);
        $user = User::factory()->create(['referrer_id' => $levelOne->id]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(19.99, 'USD', 'tok_visa', Mockery::on(fn ($metadata) => $metadata['plan_id'] === 'gold_monthly'
                && $metadata['plan_name'] === 'gold'
                && $metadata['stripe_price'] === 'price_premium_monthly'
                && $metadata['duration_days'] === 30
                && $metadata['description'] === 'Premium Subscription'))
            ->andReturn(new PaymentResult(true, 'ch_referral', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->actingAs($user)->postJson('/api/premium/purchase', [
            'payment_method_id' => 'tok_visa',
        ])->assertOk();

        $this->assertDatabaseHas('referral_commissions', [
            'purchaser_user_id' => $user->id,
            'beneficiary_user_id' => $levelOne->id,
            'level' => 1,
            'cash_amount' => 2.00,
            'token_amount' => 50.0000,
        ]);

        $this->assertDatabaseHas('referral_commissions', [
            'purchaser_user_id' => $user->id,
            'beneficiary_user_id' => $levelTwo->id,
            'level' => 2,
            'cash_amount' => 0.50,
            'token_amount' => 15.0000,
        ]);

        $this->assertEquals(50.0, (float) $levelOne->fresh()->token_balance);
        $this->assertEquals(15.0, (float) $levelTwo->fresh()->token_balance);
    }

    public function test_stripe_purchase_uses_configured_plan_pricing()
    {
        $user = User::factory()->create();

        config([
            'premium.plans.gold_monthly.price_usd' => 24.99,
            'premium.plans.gold_monthly.duration_days' => 45,
            'premium.plans.gold_monthly.stripe_price' => 'price_gold_monthly_custom',
            'premium.plans.gold_monthly.description' => 'Gold Monthly Subscription',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(24.99, 'USD', 'tok_visa', Mockery::on(fn ($metadata) => $metadata['plan_id'] === 'gold_monthly'
                && $metadata['stripe_price'] === 'price_gold_monthly_custom'
                && $metadata['duration_days'] === 45
                && $metadata['description'] === 'Gold Monthly Subscription'))
            ->andReturn(new PaymentResult(true, 'ch_plan', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method_id' => 'tok_visa',
                'plan_id' => 'gold_monthly',
            ])
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'ch_plan',
            'amount' => 24.99,
            'description' => 'Gold Monthly Subscription',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_id' => 'ch_plan',
            'stripe_price' => 'price_gold_monthly_custom',
        ]);
    }

    public function test_can_purchase_premium_with_tokens()
    {
        $user = User::factory()->create(['token_balance' => 300, 'last_daily_bonus_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method' => 'token',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Premium purchased successfully',
                'tier' => 'gold',
            ]);

        $this->assertEquals(100, $user->fresh()->token_balance); // 300 - 200
        $this->assertEquals('gold', $user->fresh()->tier);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'amount' => -200,
            'type' => 'spend',
        ]);
    }

    public function test_token_purchase_awards_token_only_referral_commissions()
    {
        $levelTwo = User::factory()->create();
        $levelOne = User::factory()->create(['referrer_id' => $levelTwo->id]);
        $user = User::factory()->create([
            'referrer_id' => $levelOne->id,
            'token_balance' => 300,
            'last_daily_bonus_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method' => 'token',
            ])
            ->assertOk();

        $this->assertDatabaseHas('referral_commissions', [
            'purchaser_user_id' => $user->id,
            'beneficiary_user_id' => $levelOne->id,
            'level' => 1,
            'cash_amount' => 0.00,
            'token_amount' => 50.0000,
        ]);

        $this->assertDatabaseHas('referral_commissions', [
            'purchaser_user_id' => $user->id,
            'beneficiary_user_id' => $levelTwo->id,
            'level' => 2,
            'cash_amount' => 0.00,
            'token_amount' => 15.0000,
        ]);

        $this->assertEquals(50.0, (float) $levelOne->fresh()->token_balance);
        $this->assertEquals(15.0, (float) $levelTwo->fresh()->token_balance);
    }

    public function test_stripe_purchase_requires_payment_proof()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase');

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'A Stripe payment method or confirmed payment intent is required.',
            ]);
    }

    public function test_cannot_purchase_premium_with_insufficient_tokens()
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method' => 'token',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Insufficient token balance.']);
    }
}
