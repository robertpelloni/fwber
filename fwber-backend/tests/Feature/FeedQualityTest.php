<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ApiToken;
use Tests\Traits\RefreshDatabaseSilently;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class FeedQualityTest extends TestCase
{
    use RefreshDatabaseSilently;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Test User',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'location_description' => 'Test City',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
            'preferences' => null, // No preferences = no filtering except request params
        ]);
        
        $this->token = ApiToken::generateForUser($this->user, 'test');
    }

    #[Test]
    public function age_min_filter_excludes_younger_users(): void
    {
        Cache::flush();
        
        $young = $this->createCandidate(20, 'female', 40.7, -74.0);
        $perfect = $this->createCandidate(28, 'female', 40.7, -74.0);
        $older = $this->createCandidate(35, 'female', 40.7, -74.0);
        
        $response = $this->getJson('/api/matches?age_min=25', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertOk();
        $matchIds = collect($response->json('matches'))->pluck('id')->toArray();
        
        $this->assertNotContains($young->id, $matchIds);
        $this->assertContains($perfect->id, $matchIds);
        $this->assertContains($older->id, $matchIds);
    }

    #[Test]
    public function age_max_filter_excludes_older_users(): void
    {
        Cache::flush();
        
        $young = $this->createCandidate(22, 'female', 40.7, -74.0); // In range
        $perfect = $this->createCandidate(28, 'female', 40.7, -74.0); // In range
        $old = $this->createCandidate(45, 'female', 40.7, -74.0); // Too old
        
        $response = $this->getJson('/api/matches?age_max=40', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertOk();
        $matchIds = collect($response->json('matches'))->pluck('id')->toArray();
        
        $this->assertContains($young->id, $matchIds);
        $this->assertContains($perfect->id, $matchIds);
        $this->assertNotContains($old->id, $matchIds);
    }

    #[Test]
    public function max_distance_filter_excludes_far_users(): void
    {
        Cache::flush();
        
        // Create users at different distances (NYC lat/lon: 40.7128, -74.0060)
        $nearby = $this->createCandidate(25, 'female', 40.7500, -74.0060); // ~3 miles
        $far = $this->createCandidate(25, 'female', 42.7128, -74.0060); // ~138 miles (well beyond 50)
        
        $response = $this->getJson('/api/matches?max_distance=50', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertOk();
        $matchIds = collect($response->json('matches'))->pluck('id')->toArray();
        
        $this->assertContains($nearby->id, $matchIds);
        $this->assertNotContains($far->id, $matchIds);
    }

    #[Test]
    public function feed_returns_empty_gracefully_when_no_matches(): void
    {
        // Create no compatible candidates
        $response = $this->getJson('/api/matches', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertOk();
        $response->assertJson([
            'matches' => [],
            'total' => 0,
        ]);
    }

    #[Test]
    public function feed_results_are_cached_for_60_seconds(): void
    {
        $candidate = $this->createCandidate(28, 'female', 40.7, -74.0);
        
        // First request
        $response1 = $this->getJson('/api/matches', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        // Delete the candidate
        $candidate->delete();
        
        // Second request should still return cached result
        $response2 = $this->getJson('/api/matches', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response1->assertOk();
        $response2->assertOk();
        
        // Both should have same results (from cache)
        $this->assertEquals(
            $response1->json('matches'),
            $response2->json('matches')
        );
        $this->assertEquals(1, $response2->json('total'));
    }

    #[Test]
    public function recently_active_users_rank_higher(): void
    {
        // Create two identical candidates except activity time
        $recentUser = $this->createCandidate(28, 'female', 40.7, -74.0);
        $recentUser->update(['last_seen_at' => now()->subMinutes(30)]);
        
        $oldUser = $this->createCandidate(28, 'female', 40.7, -74.0);
        $oldUser->update(['last_seen_at' => now()->subDays(10)]);
        
        $response = $this->getJson('/api/matches', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertOk();
        $matchIds = collect($response->json('matches'))->pluck('id')->toArray();
        
        // Recent user should appear before old user
        $recentIndex = array_search($recentUser->id, $matchIds);
        $oldIndex = array_search($oldUser->id, $matchIds);
        
        $this->assertLessThan($oldIndex, $recentIndex);
    }

    #[Test]
    public function invalid_age_filters_return_validation_error(): void
    {
        $response = $this->getJson('/api/matches?age_min=10', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['age_min']);
    }

    #[Test]
    public function invalid_distance_filter_returns_validation_error(): void
    {
        $response = $this->getJson('/api/matches?max_distance=1000', [
            'Authorization' => "Bearer {$this->token}",
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['max_distance']);
    }

    private function createCandidate(int $age, string $gender, float $lat, float $lon): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => fake()->name(),
            'date_of_birth' => now()->subYears($age),
            'gender' => $gender,
            'location_description' => fake()->city(),
            'location_latitude' => $lat,
            'location_longitude' => $lon,
            'preferences' => [
                'age_range' => ['min' => 18, 'max' => 100],
                'gender_preferences' => [
                    'male' => true,
                    'female' => true,
                    'non_binary' => true,
                ],
            ],
        ]);
        
        return $user;
    }
}
