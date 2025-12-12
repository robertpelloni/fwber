<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaidEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_rsvp_to_free_event()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['price' => 0]);

        $response = $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
            'paid' => 0,
        ]);
    }

    public function test_can_pay_for_event_with_tokens()
    {
        $user = User::factory()->create(['token_balance' => 200]);
        $event = Event::factory()->create(['price' => 10.00]); // 100 Tokens

        $response = $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending',
                'payment_method' => 'token'
            ]);

        $response->assertStatus(200);
        
        $this->assertEquals(100, $user->fresh()->token_balance); // 200 - (10 * 10)
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
            'paid' => 1,
            'payment_method' => 'token',
        ]);
    }

    public function test_cannot_pay_with_insufficient_tokens()
    {
        $user = User::factory()->create(['token_balance' => 50]);
        $event = Event::factory()->create(['price' => 10.00]); // 100 Tokens

        $response = $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending',
                'payment_method' => 'token'
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Insufficient token balance.']);
    }

    public function test_can_pay_for_event_with_stripe()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['price' => 10.00]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('charge')
            ->once()
            ->with(10.00, 'USD', 'tok_visa')
            ->andReturn(new PaymentResult(true, 'ch_event', null, []));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending',
                'payment_method' => 'stripe',
                'payment_method_id' => 'tok_visa'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
            'paid' => 1,
            'payment_method' => 'stripe',
        ]);
    }

    public function test_does_not_charge_again_if_already_paid()
    {
        $user = User::factory()->create(['token_balance' => 200]);
        $event = Event::factory()->create(['price' => 10.00]);

        // First RSVP (Pay)
        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending',
                'payment_method' => 'token'
            ]);

        $this->assertEquals(100, $user->fresh()->token_balance);

        // Change to maybe
        $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'maybe'
            ]);

        // Change back to attending (Should not charge)
        $response = $this->actingAs($user)
            ->postJson("/api/events/{$event->id}/rsvp", [
                'status' => 'attending',
                'payment_method' => 'token'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(100, $user->fresh()->token_balance); // Still 100
    }
}
