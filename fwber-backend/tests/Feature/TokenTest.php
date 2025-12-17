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
        // Users are created with last_daily_bonus_at = null by default.
        // The CheckDailyBonus middleware runs on API requests, awarding 10 tokens.
        // We set last_daily_bonus_at to now() to prevent the bonus from firing during the test.
        $sender = User::factory()->create([
            'token_balance' => 100,
            'last_daily_bonus_at' => now(),
        ]);
        $recipient = User::factory()->create([
            'token_balance' => 0,
            'last_daily_bonus_at' => now(),
        ]);

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

        $user = User::factory()->create([
            'token_balance' => 100,
            'last_daily_bonus_at' => now(), // Prevent daily bonus
        ]);

        // Valid Solana address (base58, no 0, O, I, l)
        $validSolanaAddress = '7AESM9x1q23456789112345678911234567891123456';

        $response = $this->actingAs($user)
            ->postJson('/api/wallet/withdraw', [
                'amount' => 50,
                'destination_address' => $validSolanaAddress
            ]);

        $response->assertStatus(200)
            ->assertJson(['signature' => 'mock_sig_123']);

        $this->assertEquals(50, $user->fresh()->token_balance);
    }
}
