<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_subscriptions()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_123',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/subscriptions');

        $response->assertStatus(200)
            ->assertJsonFragment(['stripe_id' => 'sub_123']);
    }

    public function test_user_can_view_payment_history()
    {
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => 19.99,
            'currency' => 'USD',
            'payment_gateway' => 'stripe',
            'transaction_id' => 'tx_123',
            'status' => 'succeeded',
            'description' => 'Test Payment',
        ]);

        $response = $this->actingAs($user)->getJson('/api/subscriptions/history');

        $response->assertStatus(200)
            ->assertJsonFragment(['transaction_id' => 'tx_123']);
    }

    public function test_user_can_view_single_subscription()
    {
        $user = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_id' => 'sub_single',
            'stripe_status' => 'active',
            'stripe_price' => 'price_123',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/subscriptions/' . $subscription->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['stripe_id' => 'sub_single']);
    }
}
