<?php

namespace Tests\Feature;

use App\Models\MatchAction;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PremiumRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_status_and_plan_routes_are_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/premium/plans')
            ->assertOk()
            ->assertJsonPath('plans.0.id', 'gold_monthly');

        $this->actingAs($user)
            ->getJson('/api/premium/status')
            ->assertOk()
            ->assertJson([
                'is_premium' => false,
                'tier' => 'free',
            ]);
    }

    public function test_mock_purchase_grants_gold_and_persists_billing_records(): void
    {
        $user = User::factory()->create();

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                transactionId: 'ch_mock_restored_123',
                message: 'Payment successful',
                data: ['status' => 'succeeded']
            ));

        $this->app->instance(PaymentGatewayInterface::class, $gateway);
        config()->set('services.payment.driver', 'mock');

        $response = $this->actingAs($user)->postJson('/api/premium/purchase', [
            'plan_id' => 'gold_monthly',
        ]);

        $response->assertOk();
        $response->assertJsonPath('plan', 'gold');

        $user->refresh();
        $this->assertSame('gold', $user->tier);
        $this->assertTrue($user->unlimited_swipes);
        $this->assertNotNull($user->tier_expires_at);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'transaction_id' => 'ch_mock_restored_123',
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'name' => 'gold',
            'stripe_status' => 'active',
        ]);

        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Subscription::count());
    }

    public function test_who_likes_you_requires_premium_then_reveals_admirers(): void
    {
        $user = User::factory()->create();
        $admirer = User::factory()->create(['name' => 'Admirer']);

        MatchAction::create([
            'user_id' => $admirer->id,
            'target_user_id' => $user->id,
            'action' => 'like',
        ]);

        $this->actingAs($user)
            ->getJson('/api/premium/who-likes-you')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Premium required',
                'locked' => true,
                'count' => 1,
            ]);

        $user->forceFill([
            'tier' => 'gold',
            'tier_expires_at' => now()->addDays(30),
            'unlimited_swipes' => true,
        ])->save();

        $this->actingAs($user)
            ->getJson('/api/premium/who-likes-you')
            ->assertOk()
            ->assertJsonPath('users.0.name', 'Admirer');
    }
}
