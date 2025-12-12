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
            ->with(19.99, 'USD', 'tok_visa')
            ->andReturn(new PaymentResult(true, 'ch_premium', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method_id' => 'tok_visa'
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

    public function test_can_purchase_premium_with_tokens()
    {
        $user = User::factory()->create(['token_balance' => 300]);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method' => 'token'
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

    public function test_cannot_purchase_premium_with_insufficient_tokens()
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/premium/purchase', [
                'payment_method' => 'token'
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Insufficient token balance.']);
    }
}
