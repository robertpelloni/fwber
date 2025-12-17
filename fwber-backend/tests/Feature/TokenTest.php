<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_transfer_tokens()
    {
        $sender = User::factory()->create(['token_balance' => 100]);
        $recipient = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($sender)
            ->postJson('/api/wallet/transfer', [
                'amount' => 50,
                'recipient_id' => $recipient->id
            ]);

        $response->assertStatus(200);

        $this->assertEquals(50, $sender->fresh()->token_balance);
        $this->assertEquals(50, $recipient->fresh()->token_balance);
    }

    public function test_user_cannot_transfer_insufficient_funds()
    {
        $sender = User::factory()->create(['token_balance' => 10]);
        $recipient = User::factory()->create(['token_balance' => 0]);

        $response = $this->actingAs($sender)
            ->postJson('/api/wallet/transfer', [
                'amount' => 50,
                'recipient_id' => $recipient->id
            ]);

        $response->assertStatus(400);
    }

    public function test_user_cannot_tip_self()
    {
        $sender = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($sender)
            ->postJson('/api/wallet/transfer', [
                'amount' => 50,
                'recipient_id' => $sender->id
            ]);

        $response->assertStatus(400);
    }

    public function test_withdraw_success_mock()
    {
        \Illuminate\Support\Facades\Process::fake([
            '*' => \Illuminate\Support\Facades\Process::result(
                output: "TX_SIGNATURE: mock_sig_123\n",
                exitCode: 0
            ),
        ]);

        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/wallet/withdraw', [
                'amount' => 50,
                'destination_address' => 'SolanaAddress123'
            ]);

        $response->assertStatus(200)
            ->assertJson(['signature' => 'mock_sig_123']);

        $this->assertEquals(50, $user->fresh()->token_balance);
    }
}
