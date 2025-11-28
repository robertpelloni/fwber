<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AIMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AIMatchingService $matchingService;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
        $this->matchingService = new AIMatchingService();
        
        // Register SQLite functions for distance calculations if needed
        $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        try {
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('radians', 'deg2rad', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
        } catch (\Exception $e) {
            // Functions might already exist
        }
    }

    public function test_detailed_preference_scoring()
    {
        // Create User A (The Searcher)
        $userA = User::factory()->create();
        $profileA = UserProfile::factory()->create([
            'user_id' => $userA->id,
            'location_latitude' => null,
            'location_longitude' => null,
            'preferences' => [
                'want_body_muscular' => 10,
                'want_ethnicity_latino' => 8,
                'want_safe_sex' => 1,
                'want_roleplay' => 1,
                'bedroom_personality' => 'sub', // Wants a Dom
                'smoke' => 0,
                'no_smoke' => 1, // Hates smoking
            ]
        ]);

        // Create User B (The Perfect Match)
        $userB = User::factory()->create();
        $profileB = UserProfile::factory()->create([
            'user_id' => $userB->id,
            'location_latitude' => null,
            'location_longitude' => null,
            'preferences' => [
                'body_type' => 'muscular',
                'ethnicity' => 'latino',
                'want_safe_sex' => 1,
                'want_roleplay' => 1,
                'bedroom_personality' => 'dom', // Is a Dom
                'smoke' => 0,
            ]
        ]);

        // Create User C (The Mismatch)
        $userC = User::factory()->create();
        $profileC = UserProfile::factory()->create([
            'user_id' => $userC->id,
            'location_latitude' => null,
            'location_longitude' => null,
            'preferences' => [
                'body_type' => 'slim',
                'ethnicity' => 'white',
                'want_safe_sex' => 0,
                'bedroom_personality' => 'sub', // Mismatch
                'smoke' => 1, // Smoker (Dealbreaker)
            ]
        ]);

        // We need to use reflection to access the private method calculateDetailedPreferenceScore
        // Or we can just call findAdvancedMatches and check the ordering/scores if exposed.
        // Since findAdvancedMatches returns an array, let's use that.

        $matches = $this->matchingService->findAdvancedMatches($userA);

        // User B should be ranked higher than User C
        $this->assertNotEmpty($matches);
        
        // Find B and C in matches
        $matchB = null;
        $matchC = null;
        
        foreach ($matches as $match) {
            if ($match['id'] === $userB->id) $matchB = $match;
            if ($match['id'] === $userC->id) $matchC = $match;
        }

        $this->assertNotNull($matchB, 'User B should be a match');
        $this->assertNotNull($matchC, 'User C should be a match (even if low score)');
        
        $this->assertGreaterThan($matchC['ai_score'], $matchB['ai_score'], 'User B should have a higher score than User C');
    }
}
