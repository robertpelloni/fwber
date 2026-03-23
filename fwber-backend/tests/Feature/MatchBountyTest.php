<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MatchBounty;
use App\Models\MatchAssist;
use App\Models\MatchAction;
use App\Services\MatchMakerService;
use Illuminate\Support\Facades\Log;

class MatchBountyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_bounty_with_token_escrow()
    {
        $user = User::factory()->create([
            'token_balance' => 1000,
            'last_daily_bonus_at' => now()
        ]);

        $response = $this->actingAs($user)->postJson('/api/matchmaker/bounty', [
            'token_reward' => 500
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('match_bounties', [
            'user_id' => $user->id,
            'token_reward' => 500,
            'status' => 'active'
        ]);

        // Check if tokens were deducted (escrowed)
        $this->assertEquals(500, $user->fresh()->token_balance);
    }

    public function test_matchmaker_gets_bounty_reward_on_match()
    {
        $creator = User::factory()->create([
            'token_balance' => 1000,
            'last_daily_bonus_at' => now()
        ]);
        \App\Models\UserProfile::factory()->create([
            'user_id' => $creator->id,
            'gender' => 'female',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferences' => ['gender_preferences' => ['male' => true], 'max_distance' => 100]
        ]);

        $wingman = User::factory()->create([
            'token_balance' => 0,
            'last_daily_bonus_at' => now()
        ]);

        $candidate = User::factory()->create([
            'last_daily_bonus_at' => now()
        ]);
        \App\Models\UserProfile::factory()->create([
            'user_id' => $candidate->id,
            'gender' => 'male',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferences' => ['gender_preferences' => ['female' => true], 'max_distance' => 100]
        ]);

        // 1. Create bounty
        $bounty = app(MatchMakerService::class)->createBounty($creator, 500);

        // 2. Wingman suggests candidate
        $this->actingAs($wingman)->postJson("/api/matchmaker/bounty/{$bounty->slug}/suggest", [
            'candidate_id' => $candidate->id
        ]);

        $this->assertDatabaseHas('match_assists', [
            'match_bounty_id' => $bounty->id,
            'matchmaker_id' => $wingman->id,
            'target_id' => $candidate->id
        ]);

        // 3. Complete the match (Candidate likes Creator)
        Log::info("TEST: Candidate ({$candidate->id}) liking Creator ({$creator->id})");
        $this->actingAs($candidate)->postJson('/api/matches/action', [
            'target_user_id' => $creator->id,
            'action' => 'like'
        ]);

        // 4. Creator likes Candidate
        Log::info("TEST: Creator ({$creator->id}) liking Candidate ({$candidate->id})");
        $response = $this->actingAs($creator)->postJson('/api/matches/action', [
            'target_user_id' => $candidate->id,
            'action' => 'like'
        ]);

        $response->assertJson(['is_match' => true]);

        // 5. Verify wingman got the 500 tokens
        $this->assertEquals(500, $wingman->fresh()->token_balance);
        
        // 6. Verify bounty is marked fulfilled
        $this->assertEquals('fulfilled', $bounty->fresh()->status);
    }
}