<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\UserProfile;
use App\Models\ContentUnlock;
use App\Services\TokenDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;
use App\Http\Middleware\CheckDailyBonus;

class MatchInsightsUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable Daily Bonus middleware to avoid side effects on token balance
        $this->withoutMiddleware([CheckDailyBonus::class]);
    }

    public function test_can_view_insights_locked_initially()
    {
        // No mock needed for read
        $user = User::factory()->create();
        $target = User::factory()->create();

        UserProfile::factory()->create(['user_id' => $user->id]);
        UserProfile::factory()->create(['user_id' => $target->id]);

        $match = UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/matches/{$match->id}/insights");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_locked' => true,
                    'cost' => 10
                ]
            ]);
    }

    public function test_can_unlock_insights_with_tokens()
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $target = User::factory()->create();

        UserProfile::factory()->create(['user_id' => $user->id]);
        UserProfile::factory()->create(['user_id' => $target->id]);

        $match = UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
        ]);

        // Mock the service
        $this->mock(TokenDistributionService::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('spendTokens')
                ->once()
                ->with(\Mockery::on(function($u) use ($user) {
                    return $u->id === $user->id;
                }), 10, "Unlocked Match Insight");
        });

        $response = $this->actingAs($user)->postJson("/api/matches/{$match->id}/insights/unlock");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Unlocked successfully']);

        $this->assertDatabaseHas('content_unlocks', [
            'user_id' => $user->id,
            'content_type' => 'match_insight',
            'content_id' => $target->id
        ]);
    }

    public function test_cannot_unlock_without_tokens()
    {
        $user = User::factory()->create(['token_balance' => 0]);
        $target = User::factory()->create();

        UserProfile::factory()->create(['user_id' => $user->id]);
        UserProfile::factory()->create(['user_id' => $target->id]);

        $match = UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
        ]);

        // Mock failure
        $this->mock(TokenDistributionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('spendTokens')
                ->once()
                ->andThrow(new \Exception("Insufficient tokens"));
        });

        $response = $this->actingAs($user)->postJson("/api/matches/{$match->id}/insights/unlock");

        $response->assertStatus(402);

        $this->assertDatabaseMissing('content_unlocks', [
            'user_id' => $user->id,
            'content_type' => 'match_insight',
            'content_id' => $target->id
        ]);
    }

    public function test_view_unlocked_insights()
    {
        $user = User::factory()->create(['token_balance' => 100]);
        $target = User::factory()->create();

        UserProfile::factory()->create(['user_id' => $user->id]);
        UserProfile::factory()->create(['user_id' => $target->id]);

        $match = UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $target->id,
        ]);

        // Create unlock record directly
        ContentUnlock::create([
            'user_id' => $user->id,
            'content_type' => 'match_insight',
            'content_id' => $target->id,
            'cost' => 10
        ]);

        $response = $this->actingAs($user)->getJson("/api/matches/{$match->id}/insights");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonStructure(['data' => ['ai_explanation', 'breakdown']]);
    }
}
