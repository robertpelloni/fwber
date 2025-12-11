<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiWingmanService;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\Models\User;
use App\Models\UserProfile;
use Mockery;
use App\DTOs\LlmResponse;

class AiWingmanServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_ice_breakers_returns_suggestions()
    {
        // Mock Users and Profiles
        $userProfile = Mockery::mock(UserProfile::class);
        $userProfile->shouldReceive('getAttribute')->with('interests')->andReturn(['coding', 'gaming']);
        $userProfile->shouldReceive('offsetExists')->andReturn(true);
        
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('profile')->andReturn($userProfile);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('offsetExists')->with('profile')->andReturn(true);

        $matchProfile = Mockery::mock(UserProfile::class);
        $matchProfile->shouldReceive('getAttribute')->with('interests')->andReturn(['gaming', 'hiking']);
        $matchProfile->shouldReceive('getAttribute')->with('bio')->andReturn('Love the outdoors.');
        $matchProfile->shouldReceive('offsetExists')->andReturn(true);

        $match = Mockery::mock(User::class);
        $match->shouldReceive('getAttribute')->with('profile')->andReturn($matchProfile);
        $match->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $match->shouldReceive('offsetExists')->with('profile')->andReturn(true);

        // Mock LLM Response
        $jsonResponse = json_encode([
            "Hey! I see you like gaming too. What are you playing lately?",
            "I noticed you love hiking. Any favorite trails?",
            "Hi! Your bio caught my eye."
        ]);

        $responseObj = new LlmResponse(
            content: $jsonResponse,
            provider: 'openai'
        );

        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->once()
            ->andReturn($responseObj);

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')
            ->andReturn($mockDriver);

        $service = new AiWingmanService($mockLlmManager);
        $suggestions = $service->generateIceBreakers($user, $match);

        $this->assertCount(3, $suggestions);
        $this->assertEquals("Hey! I see you like gaming too. What are you playing lately?", $suggestions[0]);
    }

    public function test_generate_reply_suggestions_returns_suggestions()
    {
        // Mock Users
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $match = Mockery::mock(User::class);
        $match->shouldReceive('getAttribute')->with('id')->andReturn(2);

        $history = [
            ['sender_id' => 1, 'content' => 'Hi!'],
            ['sender_id' => 2, 'content' => 'Hello! How are you?']
        ];

        // Mock LLM Response
        $jsonResponse = json_encode([
            "I'm doing great, thanks! How about you?",
            "Pretty good! Just finished work.",
            "Can't complain. What are you up to?"
        ]);

        $responseObj = new LlmResponse(
            content: $jsonResponse,
            provider: 'openai'
        );

        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->once()
            ->andReturn($responseObj);

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')
            ->andReturn($mockDriver);

        $service = new AiWingmanService($mockLlmManager);
        $suggestions = $service->generateReplySuggestions($user, $match, $history);

        $this->assertCount(3, $suggestions);
        $this->assertEquals("I'm doing great, thanks! How about you?", $suggestions[0]);
    }

    public function test_handles_llm_failure_gracefully()
    {
        // Mock Users
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('profile')->andReturn(null);
        $user->shouldReceive('offsetExists')->with('profile')->andReturn(true);
        
        $match = Mockery::mock(User::class);
        $match->shouldReceive('getAttribute')->with('profile')->andReturn(null);
        $match->shouldReceive('offsetExists')->with('profile')->andReturn(true);

        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->andThrow(new \Exception('API Error'));

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')
            ->andReturn($mockDriver);

        $service = new AiWingmanService($mockLlmManager);
        
        // Should return fallback suggestions
        $suggestions = $service->generateIceBreakers($user, $match);
        
        $this->assertNotEmpty($suggestions);
        $this->assertEquals("Hi! How's it going?", $suggestions[0]);
    }
}
