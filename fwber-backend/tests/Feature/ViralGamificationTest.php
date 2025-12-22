<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vouch;
use App\Models\Achievement;
use App\Models\ViralContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ViralGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Migrations (including the new seeding migration) are run by RefreshDatabase
    }

    public function test_icon_achievement_unlocked_at_50_vouches()
    {
        $user = User::factory()->create(['referral_code' => 'TESTICON', 'token_balance' => 0]);

        // Create 49 vouches with different IPs to simulate unique vouches
        for ($i = 0; $i < 49; $i++) {
            Vouch::create([
                'to_user_id' => $user->id,
                'type' => 'hot',
                'ip_address' => "192.168.1.{$i}",
            ]);
        }

        // Verify not unlocked yet
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('name', 'Icon')->first()->id
        ]);

        // Add 50th vouch via API
        $response = $this->postJson('/api/public/vouch', [
            'referral_code' => $user->referral_code,
            'type' => 'hot',
        ]);

        $response->assertStatus(200);

        // Verify unlocked
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('name', 'Icon')->first()->id
        ]);

        // Check tokens awarded (500)
        $this->assertEquals(500, $user->fresh()->token_balance);
    }

    public function test_viral_star_achievement_unlocked_at_100_views()
    {
        $user = User::factory()->create();
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => 'Viral content',
            'views' => 99,
            'reward_claimed' => false,
        ]);

        // Hit the endpoint to make it 100
        $this->getJson("/api/viral-content/{$content->id}");

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('name', 'Viral Star')->first()->id
        ]);
    }

    public function test_leaderboard_returns_top_vouched()
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        for ($i = 0; $i < 5; $i++) {
            Vouch::create([
                'to_user_id' => $user1->id,
                'type' => 'safe',
                'ip_address' => "10.0.0.{$i}",
            ]);
        }

        $user2 = User::factory()->create(['name' => 'Bob']);
        for ($i = 0; $i < 10; $i++) {
            Vouch::create([
                'to_user_id' => $user2->id,
                'type' => 'fun',
                'ip_address' => "10.0.1.{$i}",
            ]);
        }

        $response = $this->actingAs($user1)->getJson('/api/leaderboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'top_vouched' => [
                '*' => ['name', 'vouches']
            ]
        ]);

        $topVouched = $response->json('top_vouched');

        // Should sort by vouches desc (Bob 10, Alice 5)
        $this->assertEquals(10, $topVouched[0]['vouches']);
        $this->assertEquals(5, $topVouched[1]['vouches']);
    }
}
