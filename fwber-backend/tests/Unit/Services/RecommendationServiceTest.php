<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecommendationService;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\DTOs\LlmResponse;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private $llmManager;
    private $openaiDriver;
    private $geminiDriver;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmManager = Mockery::mock(LlmManager::class);
        $this->openaiDriver = Mockery::mock(LlmProviderInterface::class);
        $this->geminiDriver = Mockery::mock(LlmProviderInterface::class);

        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($this->openaiDriver);

        $this->llmManager->shouldReceive('driver')
            ->with('gemini')
            ->andReturn($this->geminiDriver);

        $this->service = Mockery::mock(RecommendationService::class, [$this->llmManager])->makePartial();
        $this->service->shouldAllowMockingProtectedMethods();
    }

    public function testGetRecommendationsUsesLlmManager()
    {
        // Create a user with profile
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'birthdate' => '2000-01-01',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Mock protected methods to avoid DB calls
        $this->service->shouldReceive('getUserProfile')
            ->andReturn(['id' => $user->id, 'interests' => []]);
        $this->service->shouldReceive('getUserBehavior')
            ->andReturn(['recent_activity' => []]);
        $this->service->shouldReceive('getContextualData')
            ->andReturn([]);
        $this->service->shouldReceive('getContentBasedRecommendations')
            ->andReturn([]);
        $this->service->shouldReceive('getCollaborativeRecommendations')
            ->andReturn([]);
        $this->service->shouldReceive('getLocationBasedRecommendations')
            ->andReturn([]);

        // Mock OpenAI response
        $openaiResponse = new LlmResponse(
            content: json_encode([
                [
                    'content' => ['title' => 'Tech Meetup', 'description' => 'A meetup for tech enthusiasts'],
                    'reason' => 'Matches your interest in tech',
                    'score' => 0.9
                ]
            ]),
            provider: 'openai',
            metadata: []
        );

        $this->openaiDriver->shouldReceive('chat')
            ->once()
            ->andReturn($openaiResponse);

        // Mock Gemini response
        $geminiResponse = new LlmResponse(
            content: json_encode([
                [
                    'content' => ['title' => 'Concert', 'description' => 'Live music event'],
                    'reason' => 'Matches your interest in music',
                    'score' => 0.85
                ]
            ]),
            provider: 'gemini',
            metadata: []
        );

        $this->geminiDriver->shouldReceive('chat')
            ->once()
            ->andReturn($geminiResponse);

        $recommendations = $this->service->getRecommendations($user->id);

        $this->assertIsArray($recommendations);
        
        $titles = array_map(fn($r) => $r['content']['title'], $recommendations);
        $this->assertContains('Tech Meetup', $titles);
        $this->assertContains('Concert', $titles);
    }
}
