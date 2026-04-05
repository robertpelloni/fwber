<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BoostRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_active_boost_status_and_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/boosts/active')
            ->assertOk()
            ->assertJsonPath('data', null);

        $boost = Boost::query()->create([
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'boost_type' => 'standard',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson('/api/boosts/active')
            ->assertOk()
            ->assertJsonPath('data.id', $boost->id)
            ->assertJsonPath('data.boost_type', 'standard');

        $this->actingAs($user)
            ->getJson('/api/boosts/history')
            ->assertOk()
            ->assertJsonPath('0.id', $boost->id);
    }

    public function test_user_can_purchase_boost_with_tokens(): void
    {
        $user = User::factory()->create(['token_balance' => 125]);

        $response = $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'standard',
                'payment_method' => 'token',
            ])
            ->assertOk()
            ->assertJsonPath('boost_type', 'standard')
            ->assertJsonPath('status', 'active');

        $user->refresh();
        $this->assertSame('75.00', number_format((float) $user->token_balance, 2, '.', ''));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'boost_purchase',
        ]);
    }

    public function test_user_can_purchase_boost_with_mock_card_flow(): void
    {
        $user = User::factory()->create(['token_balance' => 0]);

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                transactionId: 'boost_charge_123',
                message: 'Payment successful',
                data: ['status' => 'succeeded']
            ));

        $this->app->instance(PaymentGatewayInterface::class, $gateway);

        $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'super',
                'payment_method' => 'stripe',
                'payment_method_id' => 'pm_mock_123',
            ])
            ->assertOk()
            ->assertJsonPath('boost_type', 'super');

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'boost_charge_123',
            'status' => 'succeeded',
        ]);
    }

    public function test_token_purchase_requires_sufficient_balance(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->actingAs($user)
            ->postJson('/api/boosts/purchase', [
                'type' => 'super',
                'payment_method' => 'token',
            ])
            ->assertStatus(402)
            ->assertJsonPath('error', 'Insufficient token balance.');
    }
}
