<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BoostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_purchase_standard_boost()
    {
        $user = User::factory()->create();
        
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(4.99, 'USD', 'tok_visa')
            ->andReturn(new PaymentResult(true, 'ch_123', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
                'payment_method_id' => 'tok_visa'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'boost_type' => 'standard',
                'status' => 'active',
            ]);

        $this->assertDatabaseHas('boosts', [
            'user_id' => $user->id,
            'boost_type' => 'standard',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 4.99,
            'status' => 'succeeded',
        ]);
    }

    public function test_can_purchase_super_boost()
    {
        $user = User::factory()->create();
        
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(9.99, 'USD', 'tok_visa')
            ->andReturn(new PaymentResult(true, 'ch_456', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'super',
                'payment_method_id' => 'tok_visa'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'boost_type' => 'super',
                'status' => 'active',
            ]);
    }

    public function test_cannot_purchase_boost_if_already_active()
    {
        $user = User::factory()->create();
        Boost::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'boost_type' => 'standard',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'You already have an active boost']);
    }

    public function test_handles_payment_failure()
    {
        $user = User::factory()->create();
        
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResult(false, null, 'Card declined', []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Payment failed: Card declined']);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
    }

    public function test_can_get_active_boost()
    {
        $user = User::factory()->create();
        $boost = Boost::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'boost_type' => 'standard',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/boosts/active');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $boost->id,
                'boost_type' => 'standard',
            ]);
    }

    public function test_returns_404_if_no_active_boost()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/boosts/active');

        $response->assertStatus(404)
            ->assertJson(['message' => 'No active boost']);
    }

    public function test_can_get_boost_history()
    {
        $user = User::factory()->create();
        Boost::create([
            'user_id' => $user->id,
            'started_at' => now()->subDay(),
            'expires_at' => now()->subDay()->addMinutes(30),
            'boost_type' => 'standard',
            'status' => 'expired',
        ]);
        Boost::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'boost_type' => 'super',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/boosts/history');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_purchase_boost_with_tokens()
    {
        $user = User::factory()->create(['token_balance' => 100, 'last_daily_bonus_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
                'payment_method' => 'token'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'boost_type' => 'standard',
                'status' => 'active',
            ]);

        $this->assertEquals(50, $user->fresh()->token_balance);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'amount' => -50,
            'type' => 'spend',
        ]);
    }

    public function test_cannot_purchase_boost_with_insufficient_tokens()
    {
        $user = User::factory()->create(['token_balance' => 10, 'last_daily_bonus_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
                'payment_method' => 'token'
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Insufficient token balance.']);
    }
}
