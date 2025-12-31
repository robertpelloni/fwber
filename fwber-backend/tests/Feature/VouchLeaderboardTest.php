<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vouch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VouchLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_endpoint_returns_vouch_data_with_breakdown()
    {
        // Create users
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        
        // Create vouches for user1 (Total 3: 2 Safe, 1 Fun)
        Vouch::factory()->create(['to_user_id' => $user1->id, 'type' => 'safe']);
        Vouch::factory()->create(['to_user_id' => $user1->id, 'type' => 'safe']);
        Vouch::factory()->create(['to_user_id' => $user1->id, 'type' => 'fun']);

        // Create vouches for user2 (Total 1: 1 Hot)
        Vouch::factory()->create(['to_user_id' => $user2->id, 'type' => 'hot']);

        // Clear cache to ensure fresh data
        Cache::forget('leaderboard_stats');

        $response = $this->actingAs($user1)
            ->getJson('/api/leaderboard');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertArrayHasKey('top_vouched', $data);
        $topVouched = $data['top_vouched'];
        
        // Check order (User 1 should be first)
        $this->assertEquals('Ali***', $topVouched[0]['name']);
        $this->assertEquals(3, $topVouched[0]['vouches']);
        
        // Check breakdown for User 1
        $this->assertEquals(2, $topVouched[0]['breakdown']['safe']);
        $this->assertEquals(1, $topVouched[0]['breakdown']['fun']);
        $this->assertEquals(0, $topVouched[0]['breakdown']['hot']);
        
        // Check User 2
        $this->assertEquals('Bob***', $topVouched[1]['name']);
        $this->assertEquals(1, $topVouched[1]['vouches']);
        $this->assertEquals(1, $topVouched[1]['breakdown']['hot']);
    }
}
