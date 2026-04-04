<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlockSafetyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocking_a_user_deactivates_existing_match_and_hides_established_conversation(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $blocker->id,
            'display_name' => 'Blocker',
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'interests' => ['music', 'privacy'],
        ]);

        UserProfile::factory()->create([
            'user_id' => $blocked->id,
            'display_name' => 'Blocked',
            'latitude' => 42.3315,
            'longitude' => -83.0457,
            'interests' => ['music', 'coffee'],
        ]);

        $matchId = DB::table('user_matches')->insertGetId([
            'user1_id' => min($blocker->id, $blocked->id),
            'user2_id' => max($blocker->id, $blocked->id),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('match_actions')->insert([
            [
                'user_id' => $blocker->id,
                'target_user_id' => $blocked->id,
                'action' => 'like',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $blocked->id,
                'target_user_id' => $blocker->id,
                'action' => 'like',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($blocker)->postJson('/api/blocks', [
            'user_id' => $blocked->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
        $this->assertDatabaseHas('user_matches', [
            'id' => $matchId,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('match_actions', [
            'user_id' => $blocker->id,
            'target_user_id' => $blocked->id,
        ]);
        $this->assertDatabaseMissing('match_actions', [
            'user_id' => $blocked->id,
            'target_user_id' => $blocker->id,
        ]);

        $establishedResponse = $this->actingAs($blocker)->getJson('/api/matches/established');

        $establishedResponse->assertOk();
        $this->assertSame([], $establishedResponse->json('data'));
    }

    public function test_blocked_users_cannot_exchange_messages(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        DB::table('user_matches')->insert([
            'user1_id' => min($sender->id, $receiver->id),
            'user2_id' => max($sender->id, $receiver->id),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Block::create([
            'blocker_id' => $sender->id,
            'blocked_id' => $receiver->id,
        ]);

        $response = $this->actingAs($sender)->postJson('/api/messages', [
            'receiver_id' => (string) $receiver->id,
            'content' => 'This should never be delivered.',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Messaging blocked between users']);
    }

    public function test_blocked_users_are_excluded_from_match_feed_candidates(): void
    {
        $viewer = User::factory()->create();
        $visibleCandidate = User::factory()->create();
        $blockedCandidate = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'display_name' => 'Viewer',
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'birthdate' => now()->subYears(29)->toDateString(),
            'interests' => ['music', 'privacy', 'coffee'],
        ]);

        UserProfile::factory()->create([
            'user_id' => $visibleCandidate->id,
            'display_name' => 'Visible Candidate',
            'latitude' => 42.33145,
            'longitude' => -83.04585,
            'birthdate' => now()->subYears(28)->toDateString(),
            'interests' => ['music', 'coffee'],
        ]);

        UserProfile::factory()->create([
            'user_id' => $blockedCandidate->id,
            'display_name' => 'Blocked Candidate',
            'latitude' => 42.33146,
            'longitude' => -83.04586,
            'birthdate' => now()->subYears(28)->toDateString(),
            'interests' => ['music', 'privacy'],
        ]);

        Block::create([
            'blocker_id' => $viewer->id,
            'blocked_id' => $blockedCandidate->id,
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/matches?max_distance=10');

        $response->assertOk();

        $matchIds = collect($response->json('matches'))->pluck('id')->all();

        $this->assertContains($visibleCandidate->id, $matchIds);
        $this->assertNotContains($blockedCandidate->id, $matchIds);
    }
}
