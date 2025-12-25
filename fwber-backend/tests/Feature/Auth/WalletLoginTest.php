<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_signature_creates_user()
    {
        // 1. Generate a valid signature using the helper script
        $message = "Login Test " . time();
        $scriptPath = base_path('scripts/solana/sign_message.cjs');
        $command = "node {$scriptPath} " . escapeshellarg($message);

        $output = shell_exec($command);
        $data = json_decode($output, true);

        $walletAddress = $data['wallet_address'];
        $signature = $data['signature'];

        // 2. Call the API
        $response = $this->postJson('/api/auth/login-wallet', [
            'wallet_address' => $walletAddress,
            'signature' => $signature,
            'message' => $message,
        ]);

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'user', 'is_new_user']);
        $this->assertTrue($response->json('is_new_user'));

        // Verify user exists in DB
        $this->assertDatabaseHas('users', [
            'wallet_address' => $walletAddress,
        ]);
    }

    public function test_login_with_invalid_signature_fails()
    {
        $response = $this->postJson('/api/auth/login-wallet', [
            'wallet_address' => 'ValidAddressButWrong',
            'signature' => 'InvalidSignature',
            'message' => 'Test',
        ]);

        $response->assertStatus(422);
    }
}
