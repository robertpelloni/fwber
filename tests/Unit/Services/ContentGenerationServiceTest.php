<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ContentGenerationService;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use Mockery;

class ContentGenerationServiceTest extends TestCase
{
    public function test_generate_content_uses_llm_manager()
    {
        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->once()
            ->andReturn(['content' => 'Generated content']);

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($mockDriver);

        $service = new ContentGenerationService($mockLlmManager);
        $result = $service->generateContent('Test prompt');

        $this->assertEquals('Generated content', $result['content']);
    }

    public function test_generate_with_multi_ai()
    {
        $mockOpenAi = Mockery::mock(LlmProviderInterface::class);
        $mockOpenAi->shouldReceive('chat')->andReturn(['content' => 'OpenAI content']);

        $mockGemini = Mockery::mock(LlmProviderInterface::class);
        $mockGemini->shouldReceive('chat')->andReturn(['content' => 'Gemini content']);

        $mockClaude = Mockery::mock(LlmProviderInterface::class);
        $mockClaude->shouldReceive('chat')->andReturn(['content' => 'Claude content']);

        $mockLlmManager = Mockery::mock(LlmManager::class);
        $mockLlmManager->shouldReceive('driver')->with('openai')->andReturn($mockOpenAi);
        $mockLlmManager->shouldReceive('driver')->with('gemini')->andReturn($mockGemini);
        $mockLlmManager->shouldReceive('driver')->with('anthropic')->andReturn($mockClaude);

        $service = new ContentGenerationService($mockLlmManager);
        $results = $service->generateWithMultiAI('Test prompt', ['openai', 'gemini', 'anthropic']);

        $this->assertEquals('OpenAI content', $results['openai']['content']);
        $this->assertEquals('Gemini content', $results['gemini']['content']);
        $this->assertEquals('Claude content', $results['anthropic']['content']);
    }
}
