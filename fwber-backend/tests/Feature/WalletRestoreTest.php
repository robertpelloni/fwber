<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_wallet_payload(): void
    {
        $user = User::factory()->create([
            'referral_code' => 'REF1234',
            'wallet_address' => 'SOLANA123',
            'token_balance' => 125.5,
            'golden_tickets_remaining' => 2,
        ]);

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 25,
            'type' => 'reward',
            'description' => 'Welcome bonus',
        ]);

        $this->actingAs($user)->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('balance', '125.50')
            ->assertJsonPath('referral_code', 'REF1234')
            ->assertJsonPath('wallet_address', 'SOLANA123')
            ->assertJsonPath('golden_tickets_remaining', 2)
            ->assertJsonCount(1, 'transactions');
    }

    public function test_authenticated_user_can_update_wallet_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/wallet/address', [
            'wallet_address' => 'NEW_SOL_ADDRESS',
        ])->assertOk()->assertJsonPath('wallet_address', 'NEW_SOL_ADDRESS');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'wallet_address' => 'NEW_SOL_ADDRESS',
        ]);
    }
}
