<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorRecoveryCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_recovery_codes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Enable 2FA first
        $this->postJson('/api/user/two-factor-authentication');

        $response = $this->getJson('/api/user/two-factor-recovery-codes');

        $response->assertStatus(200)
            ->assertJsonCount(8); // Laravel default is 8 codes
    }

    public function test_user_can_regenerate_recovery_codes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Enable 2FA first
        $this->postJson('/api/user/two-factor-authentication');

        // Get initial codes
        $initialCodes = $this->getJson('/api/user/two-factor-recovery-codes')->json();

        // Regenerate
        $response = $this->postJson('/api/user/two-factor-recovery-codes');

        $response->assertStatus(200);
        $newCodes = $response->json();

        $this->assertNotEquals($initialCodes, $newCodes);
    }
}
