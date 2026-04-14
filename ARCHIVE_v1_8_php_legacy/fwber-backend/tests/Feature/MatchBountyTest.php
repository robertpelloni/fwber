<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\UserProfile;
use App\Models\User;
use App\Services\MatchMakerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MatchBountyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_bounty_with_token_escrow()
    {
        $user = User::factory()->create([
            'token_balance' => 1000,
            'last_daily_bonus_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bounties', [
            'token_reward' => 500,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'bounty' => ['id', 'slug', 'token_reward', 'status'],
            'share_url',
        ]);

        $this->assertDatabaseHas('match_bounties', [
            'user_id' => $user->id,
            'token_reward' => 500,
            'status' => 'active',
        ]);

        // Check if tokens were deducted (escrowed)
        $this->assertEquals(500, $user->fresh()->token_balance);
    }

    public function test_bounty_index_returns_rich_user_payload_and_supports_reward_alias(): void
    {
        $viewer = User::factory()->create([
            'last_daily_bonus_at' => now(),
        ]);

        $higherRewardUser = User::factory()->create([
            'name' => 'High Reward',
            'avatar_url' => 'https://cdn.example.com/high.jpg',
            'last_daily_bonus_at' => now(),
        ]);
        UserProfile::factory()->create([
            'user_id' => $higherRewardUser->id,
            'display_name' => 'High Roller',
            'bio' => 'Loves bold introductions.',
            'gender' => 'female',
            'birthdate' => now()->subYears(29)->toDateString(),
        ]);
        Photo::factory()->create([
            'user_id' => $higherRewardUser->id,
            'file_path' => 'photos/high-primary.jpg',
            'thumbnail_path' => 'photos/high-primary-thumb.jpg',
            'is_primary' => true,
            'is_private' => false,
        ]);

        $lowerRewardUser = User::factory()->create([
            'name' => 'Low Reward',
            'avatar_url' => 'https://cdn.example.com/low.jpg',
            'last_daily_bonus_at' => now(),
        ]);
        UserProfile::factory()->create([
            'user_id' => $lowerRewardUser->id,
            'display_name' => 'Low Roller',
            'gender' => 'male',
            'birthdate' => now()->subYears(31)->toDateString(),
        ]);

        \App\Models\MatchBounty::create([
            'user_id' => $lowerRewardUser->id,
            'slug' => 'lowreward',
            'token_reward' => 100,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        \App\Models\MatchBounty::create([
            'user_id' => $higherRewardUser->id,
            'slug' => 'highreward',
            'token_reward' => 500,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/bounties?sort=reward&page=1&per_page=20');

        $response->assertOk();
        $response->assertJsonPath('data.0.slug', 'highreward');
        $response->assertJsonPath('data.0.user.profile.display_name', 'High Roller');
        $response->assertJsonPath('data.0.user.profile.gender', 'female');
        $response->assertJsonPath('data.0.user.photos.0.is_primary', true);
        $response->assertJsonStructure([
            'data' => [
                [
                    'slug',
                    'token_reward',
                    'user' => [
                        'id',
                        'name',
                        'avatar_url',
                        'profile' => ['display_name', 'bio', 'birthdate', 'gender', 'age'],
                        'photos' => [
                            ['id', 'is_primary', 'url', 'thumbnail_url'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_matchmaker_gets_bounty_reward_on_match()
    {
        $creator = User::factory()->create([
            'token_balance' => 1000,
            'last_daily_bonus_at' => now(),
        ]);
        \App\Models\UserProfile::factory()->create([
            'user_id' => $creator->id,
            'gender' => 'female',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferences' => ['gender_preferences' => ['male' => true], 'max_distance' => 100],
        ]);

        $wingman = User::factory()->create([
            'token_balance' => 0,
            'last_daily_bonus_at' => now(),
        ]);

        $candidate = User::factory()->create([
            'last_daily_bonus_at' => now(),
        ]);
        \App\Models\UserProfile::factory()->create([
            'user_id' => $candidate->id,
            'gender' => 'male',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferences' => ['gender_preferences' => ['female' => true], 'max_distance' => 100],
        ]);

        // 1. Create bounty
        $bounty = app(MatchMakerService::class)->createBounty($creator, 500);

        // 2. Wingman suggests candidate
        $this->actingAs($wingman)->postJson("/api/matchmaker/bounty/{$bounty->slug}/suggest", [
            'candidate_id' => $candidate->id,
        ]);

        $this->assertDatabaseHas('match_assists', [
            'match_bounty_id' => $bounty->id,
            'matchmaker_id' => $wingman->id,
            'target_id' => $candidate->id,
        ]);

        // 3. Complete the match (Candidate likes Creator)
        Log::info("TEST: Candidate ({$candidate->id}) liking Creator ({$creator->id})");
        $this->actingAs($candidate)->postJson('/api/matches/action', [
            'target_user_id' => $creator->id,
            'action' => 'like',
        ]);

        // 4. Creator likes Candidate
        Log::info("TEST: Creator ({$creator->id}) liking Candidate ({$candidate->id})");
        $response = $this->actingAs($creator)->postJson('/api/matches/action', [
            'target_user_id' => $candidate->id,
            'action' => 'like',
        ]);

        $response->assertJson(['is_match' => true]);

        // 5. Verify wingman got the 500 tokens
        $this->assertEquals(500, $wingman->fresh()->token_balance);

        // 6. Verify bounty is marked fulfilled
        $this->assertEquals('fulfilled', $bounty->fresh()->status);
    }
}
