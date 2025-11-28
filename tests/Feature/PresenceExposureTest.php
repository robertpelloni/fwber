<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMatch;
use Tests\Traits\RefreshDatabaseSilently;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PresenceExposureTest extends TestCase
{
    use RefreshDatabaseSilently;

    #[Test]
    public function last_seen_exposed_in_match_feed(): void
    {
        // Create user with complete profile
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Test User',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'bio' => 'Test bio',
        ]);

        // Create potential matches with various last_seen_at values
        $recentMatch = User::factory()->create(['last_seen_at' => now()->subMinutes(5)]);
        UserProfile::factory()->create([
            'user_id' => $recentMatch->id,
            'display_name' => 'Recent User',
            'date_of_birth' => now()->subYears(26),
            'gender' => 'female',
            'location_latitude' => 40.7200,
            'location_longitude' => -74.0100,
            'bio' => 'Recent',
        ]);

        $oldMatch = User::factory()->create(['last_seen_at' => now()->subDays(7)]);
        UserProfile::factory()->create([
            'user_id' => $oldMatch->id,
            'display_name' => 'Old User',
            'date_of_birth' => now()->subYears(27),
            'gender' => 'female',
            'location_latitude' => 40.7300,
            'location_longitude' => -74.0200,
            'bio' => 'Old',
        ]);

        $token = ApiToken::generateForUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/matches');

        $response->assertOk();
        
        $matches = $response->json('matches');
        $this->assertNotEmpty($matches);

        // Verify lastSeenAt is present
        foreach ($matches as $match) {
            $this->assertArrayHasKey('lastSeenAt', $match);
        }

        // Find the recent match and verify it's a valid ISO8601 string
        $recentMatchData = collect($matches)->firstWhere('id', $recentMatch->id);
        $this->assertNotNull($recentMatchData);
        $this->assertNotNull($recentMatchData['lastSeenAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $recentMatchData['lastSeenAt']);
    }

    #[Test]
    public function last_seen_exposed_in_conversation_view(): void
    {
        // Create two users with profiles and match
        $userA = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $userA->id,
            'display_name' => 'User A',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'bio' => 'A',
        ]);

        $userB = User::factory()->create(['last_seen_at' => now()->subHours(2)]);
        UserProfile::factory()->create([
            'user_id' => $userB->id,
            'display_name' => 'User B',
            'date_of_birth' => now()->subYears(26),
            'gender' => 'female',
            'bio' => 'B',
        ]);

        UserMatch::create([
            'user1_id' => min($userA->id, $userB->id),
            'user2_id' => max($userA->id, $userB->id),
            'is_active' => true,
        ]);

        $token = ApiToken::generateForUser($userA);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/messages/{$userB->id}");

        $response->assertOk();

        // Verify structure includes other_user with last_seen_at
        $response->assertJsonStructure([
            'messages',
            'pagination',
            'other_user' => ['id', 'name', 'last_seen_at'],
        ]);

        $otherUser = $response->json('other_user');
        $this->assertEquals($userB->id, $otherUser['id']);
        $this->assertNotNull($otherUser['last_seen_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $otherUser['last_seen_at']);
    }
}
