<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TokenDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_signup_bonus()
    {
        $service = new TokenDistributionService();
        $user = User::factory()->create();

        $service->processSignupBonus($user);

        $this->assertGreaterThan(0, $user->token_balance);
        $this->assertDatabaseHas('token_transactions', [
            'user_id' => $user->id,
            'type' => 'signup_bonus',
        ]);
    }

    public function test_referral_bonus()
    {
        $service = new TokenDistributionService();
        
        $referrer = User::factory()->create(['referral_code' => 'REF123']);
        $user = User::factory()->create();

        $service->processSignupBonus($user, 'REF123');

        $referrer->refresh();
        $user->refresh();

        // Referrer gets bonus
        $this->assertEquals(50, $referrer->token_balance);
        
        // User gets signup bonus + referral accepted bonus
        $this->assertGreaterThan(10, $user->token_balance);
        
        $this->assertEquals($referrer->id, $user->referrer_id);
    }

    public function test_early_adopter_decay()
    {
        $service = new TokenDistributionService();
        
        // Create 10 users
        User::factory()->count(10)->create();
        
        $user1 = User::factory()->create();
        $service->processSignupBonus($user1);
        
        // Create another 100 users
        User::factory()->count(100)->create();
        
        $user2 = User::factory()->create();
        $service->processSignupBonus($user2);
        
        // User 2 (later) should have LESS than User 1 (earlier)
        $this->assertLessThan($user1->token_balance, $user2->token_balance);
    }
}
