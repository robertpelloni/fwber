<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PaymentRequest;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;
use App\Http\Middleware\CheckDailyBonus;

class PaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckDailyBonus::class]);

        // Mock Push Service
        $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->byDefault();
        });
    }

    public function test_can_create_payment_request()
    {
        $requester = User::factory()->create();
        $payer = User::factory()->create();

        $response = $this->actingAs($requester)->postJson('/api/wallet/requests', [
            'payer_id' => $payer->id,
            'amount' => 50,
            'note' => 'For dinner',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('payment_requests', [
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_payer_can_pay_request()
    {
        $requester = User::factory()->create(['token_balance' => 0]);
        $payer = User::factory()->create(['token_balance' => 100]);

        $request = PaymentRequest::create([
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($payer)->postJson("/api/wallet/requests/{$request->id}/pay");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Payment successful']);

        // Verify balances
        $this->assertEquals(50, $payer->fresh()->token_balance); // 100 - 50
        $this->assertEquals(50, $requester->fresh()->token_balance); // 0 + 50

        // Verify status
        $this->assertEquals('paid', $request->fresh()->status);
    }

    public function test_cannot_pay_with_insufficient_funds()
    {
        $requester = User::factory()->create(['token_balance' => 0]);
        $payer = User::factory()->create(['token_balance' => 10]); // Less than 50

        $request = PaymentRequest::create([
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($payer)->postJson("/api/wallet/requests/{$request->id}/pay");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Insufficient balance']);

        // Verify balances unchanged
        $this->assertEquals(10, $payer->fresh()->token_balance);
        $this->assertEquals('pending', $request->fresh()->status);
    }

    public function test_cannot_pay_processed_request()
    {
        $requester = User::factory()->create();
        $payer = User::factory()->create(['token_balance' => 100]);

        $request = PaymentRequest::create([
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($payer)->postJson("/api/wallet/requests/{$request->id}/pay");

        $response->assertStatus(400);
    }

    public function test_requester_can_cancel()
    {
        $requester = User::factory()->create();
        $payer = User::factory()->create();

        $request = PaymentRequest::create([
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($requester)->postJson("/api/wallet/requests/{$request->id}/cancel");

        $response->assertStatus(200);
        $this->assertEquals('cancelled', $request->fresh()->status);
    }

    public function test_payer_can_decline()
    {
        $requester = User::factory()->create();
        $payer = User::factory()->create();

        $request = PaymentRequest::create([
            'requester_id' => $requester->id,
            'payer_id' => $payer->id,
            'amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($payer)->postJson("/api/wallet/requests/{$request->id}/cancel");

        $response->assertStatus(200);
        $this->assertEquals('declined', $request->fresh()->status);
    }
}
