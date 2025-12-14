<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiWingmanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class MatchInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_match_insights_with_explanation()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'I love hiking and coding.',
            'interests' => ['hiking', 'coding'],
            'birthdate' => now()->subYears(25),
        ]);
        
        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'bio' => 'I enjoy outdoor adventures and programming.',
            'interests' => ['hiking', 'programming'],
            'birthdate' => now()->subYears(26),
        ]);

        $this->actingAs($user);

        // Mock AiWingmanService
        // We need to partial mock if we want other methods to work, but here we only call generateMatchExplanation
        // However, the controller also injects AIMatchingService. We can let that run real code as it's just logic.
        
        $this->mock(AiWingmanService::class, function ($mock) use ($user, $match) {
            $mock->shouldReceive('generateMatchExplanation')
                ->andReturn('You are a perfect match because you both like coding.');
        });

        $response = $this->getJson("/api/matches/{$match->id}/insights");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ai_explanation', 'You are a perfect match because you both like coding.')
            ->assertJsonStructure([
                'data' => [
                    'total_score',
                    'breakdown',
                    'ai_explanation'
                ]
            ]);
    }
}
