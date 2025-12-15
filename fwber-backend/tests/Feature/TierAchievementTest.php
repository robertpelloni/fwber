<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\RelationshipTier;
use App\Models\User;
use App\Models\UserMatch;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TierAchievementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable the feature flag
        Config::set('features.face_reveal', true);
        
        // Seed achievements
        $this->seed(AchievementSeeder::class);
    }

    public function test_unlocks_connected_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'accepted',
        ]);

        $tier = RelationshipTier::create([
            'match_id' => $match->id,
            'current_tier' => 'matched',
            'messages_exchanged' => 9,
            'days_connected' => 1,
            'first_matched_at' => now()->subDays(1),
        ]);

        $this->actingAs($user1);

        $response = $this->putJson("/api/matches/{$match->id}/tier", [
            'increment_messages' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['current_tier' => 'connected']);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user1->id,
            'achievement_id' => Achievement::where('name', 'Connected')->first()->id,
        ]);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user2->id,
            'achievement_id' => Achievement::where('name', 'Connected')->first()->id,
        ]);
    }

    public function test_unlocks_established_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'accepted',
        ]);

        $tier = RelationshipTier::create([
            'match_id' => $match->id,
            'current_tier' => 'connected',
            'messages_exchanged' => 49,
            'days_connected' => 7,
            'first_matched_at' => now()->subDays(7),
        ]);

        $this->actingAs($user1);

        $response = $this->putJson("/api/matches/{$match->id}/tier", [
            'increment_messages' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['current_tier' => 'established']);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user1->id,
            'achievement_id' => Achievement::where('name', 'Established')->first()->id,
        ]);
    }

    public function test_unlocks_verified_achievement()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'accepted',
        ]);

        $tier = RelationshipTier::create([
            'match_id' => $match->id,
            'current_tier' => 'established',
            'messages_exchanged' => 100,
            'days_connected' => 30,
        ]);

        // User 1 confirms
        $this->actingAs($user1);
        $this->putJson("/api/matches/{$match->id}/tier", [
            'mark_met_in_person' => true,
        ])->assertStatus(200);

        // User 2 confirms
        $this->actingAs($user2);
        $response = $this->putJson("/api/matches/{$match->id}/tier", [
            'mark_met_in_person' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['current_tier' => 'verified']);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user1->id,
            'achievement_id' => Achievement::where('name', 'Verified Connection')->first()->id,
        ]);

        $this->assertDatabaseHas('achievement_user', [
            'user_id' => $user2->id,
            'achievement_id' => Achievement::where('name', 'Verified Connection')->first()->id,
        ]);
    }
}
