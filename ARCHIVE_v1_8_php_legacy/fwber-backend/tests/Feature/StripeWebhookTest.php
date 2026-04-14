<?php

namespace Tests\Feature;

use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Webhook;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_payment_intent_succeeded_creates_payment_and_grants_premium()
    {
        Carbon::setTestNow('2026-04-02 12:00:00');
        $user = User::factory()->create();

        config([
            'premium.plans.gold_monthly.duration_days' => 45,
            'premium.plans.gold_monthly.stripe_price' => 'price_gold_monthly_custom',
            'premium.plans.gold_monthly.description' => 'Gold Monthly Subscription',
        ]);

        $payload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 999,
                    'currency' => 'usd',
                    'metadata' => ['user_id' => $user->id, 'plan_id' => 'gold_monthly'],
                    'description' => 'Gold Monthly Subscription',
                ],
            ],
        ];

        $this->mockWebhookEvent($payload);

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'pi_test_123',
            'amount' => 9.99,
            'status' => 'succeeded',
            'description' => 'Gold Monthly Subscription',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_id' => 'pi_test_123',
            'stripe_price' => 'price_gold_monthly_custom',
        ]);

        $user->refresh();
        $this->assertEquals('gold', $user->tier);
        $this->assertTrue((bool) $user->unlimited_swipes);
        $this->assertEquals(Carbon::now()->addDays(45)->toDateTimeString(), $user->tier_expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_subscription_created_creates_subscription()
    {
        $user = User::factory()->create();

        $payload = [
            'id' => 'evt_test_sub_123',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'metadata' => ['user_id' => $user->id, 'plan_name' => 'gold'],
                    'items' => [
                        'data' => [
                            [
                                'price' => ['id' => 'price_test_123'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'current_period_end' => now()->addMonth()->timestamp,
                    'trial_end' => null,
                ],
            ],
        ];

        $this->mockWebhookEvent($payload);

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'active',
        ]);

        $user->refresh();
        $this->assertEquals('gold', $user->tier);
    }

    public function test_subscription_updated_updates_status()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_id' => 'sub_test_update',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->addMonth(),
        ]);

        $payload = [
            'id' => 'evt_test_update_123',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_update',
                    'status' => 'past_due',
                    'metadata' => ['user_id' => $user->id],
                    'items' => [
                        'data' => [
                            [
                                'price' => ['id' => 'price_test'],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'current_period_end' => now()->addMonth()->timestamp,
                    'trial_end' => null,
                ],
            ],
        ];

        $this->mockWebhookEvent($payload);

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'past_due',
        ]);

        $user->refresh();
        $this->assertEquals('free', $user->tier);
    }

    public function test_subscription_deleted_cancels_subscription()
    {
        $user = User::factory()->create(['tier' => 'gold']);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_id' => 'sub_test_delete',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->addMonth(),
        ]);

        $payload = [
            'id' => 'evt_test_delete_123',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_delete',
                    'status' => 'canceled',
                    'metadata' => ['user_id' => $user->id],
                ],
            ],
        ];

        $this->mockWebhookEvent($payload);

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'canceled',
        ]);

        $user->refresh();
        $this->assertEquals('free', $user->tier);
    }

    public function test_invoice_payment_succeeded_awards_renewal_referral_commissions()
    {
        $levelTwo = User::factory()->create();
        $levelOne = User::factory()->create(['referrer_id' => $levelTwo->id]);
        $user = User::factory()->create(['referrer_id' => $levelOne->id, 'tier' => 'gold']);

        Subscription::create([
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_id' => 'sub_test_renewal',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->addMonth(),
        ]);

        $payload = [
            'id' => 'evt_test_invoice_success_123',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_renewal_123',
                    'subscription' => 'sub_test_renewal',
                    'payment_intent' => 'pi_test_renewal_123',
                    'amount_paid' => 1999,
                    'currency' => 'usd',
                    'number' => 'INV-123',
                ],
            ],
        ];

        $this->mockWebhookEvent($payload);

        $this->postJson('/api/stripe/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'pi_test_renewal_123',
            'amount' => 19.99,
            'status' => 'succeeded',
        ]);

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
    }

    private function mockWebhookEvent($payload)
    {
        $event = json_decode(json_encode($payload));

        Mockery::mock('alias:'.Webhook::class)
            ->shouldReceive('constructEvent')
            ->andReturn($event);
    }
}
