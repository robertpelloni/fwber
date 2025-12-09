<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Stripe\Webhook;
use Mockery;

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
        $user = User::factory()->create();
        
        $payload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 999,
                    'currency' => 'usd',
                    'metadata' => ['user_id' => $user->id],
                    'description' => 'Premium Subscription',
                ]
            ]
        ];

        $this->mockWebhookEvent($payload);

        $response = $this->postJson('/api/stripe/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'pi_test_123',
            'amount' => 9.99,
            'status' => 'succeeded',
        ]);

        $user->refresh();
        $this->assertEquals('gold', $user->tier);
        $this->assertTrue((bool)$user->unlimited_swipes);
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
                                'quantity' => 1
                            ]
                        ]
                    ],
                    'current_period_end' => now()->addMonth()->timestamp,
                    'trial_end' => null,
                ]
            ]
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
                                'quantity' => 1
                            ]
                        ]
                    ],
                    'current_period_end' => now()->addMonth()->timestamp,
                    'trial_end' => null,
                ]
            ]
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
                ]
            ]
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

    private function mockWebhookEvent($payload)
    {
        $event = json_decode(json_encode($payload));
        
        Mockery::mock('alias:' . Webhook::class)
            ->shouldReceive('constructEvent')
            ->andReturn($event);
    }
}
