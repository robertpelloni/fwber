<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TokenDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenSpendingTest extends TestCase
{
    use RefreshDatabase;

    protected $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenDistributionService::class);
    }

    public function test_user_can_spend_tokens()
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $this->tokenService->spendTokens($user, 50, 'Test Spend');

        $this->assertEquals(50, $user->fresh()->token_balance);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'amount' => -50,
            'type' => 'spend',
            'description' => 'Test Spend',
        ]);
    }

    public function test_user_cannot_spend_more_than_balance()
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient token balance');

        $this->tokenService->spendTokens($user, 50, 'Test Spend');
    }
}
