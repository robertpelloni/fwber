<?php

namespace Tests\Unit\Services;

use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\Services\ContentModerationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ContentModerationServiceTest extends TestCase
{
    protected $llmManager;
    protected $driver;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmManager = Mockery::mock(LlmManager::class);
        $this->driver = Mockery::mock(LlmProviderInterface::class);
        
        // Default config
        Config::set('moderation', [
            'enabled' => true,
            'providers' => ['openai'],
            'thresholds' => [
                'hate' => 0.8,
                'harassment' => 0.8,
                'violence' => 0.8,
                'sexual' => 0.8,
                'spam' => 0.7,
            ],
            'cache_ttl' => 3600,
        ]);

        $this->service = new ContentModerationService($this->llmManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_moderate_content_safe()
    {
        $content = "This is safe content.";
        
        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($this->driver);

        $this->driver->shouldReceive('moderate')
            ->with($content)
            ->andReturn([
                'flagged' => false,
                'categories' => ['hate' => 0.01],
                'score' => 0.01
            ]);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once();

        $result = $this->service->moderateContent($content);

        $this->assertFalse($result['flagged']);
        $this->assertEquals('approve', $result['action']);
    }

    public function test_moderate_content_unsafe()
    {
        $content = "This is unsafe content.";
        
        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($this->driver);

        $this->driver->shouldReceive('moderate')
            ->with($content)
            ->andReturn([
                'flagged' => true,
                'categories' => ['hate' => 0.95],
                'score' => 0.95
            ]);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once();

        $result = $this->service->moderateContent($content);

        $this->assertTrue($result['flagged']);
        $this->assertEquals('reject', $result['action']);
    }

    public function test_moderate_content_cached()
    {
        $content = "Cached content.";
        $cachedResult = [
            'flagged' => false,
            'action' => 'approve'
        ];

        Cache::shouldReceive('get')
            ->once()
            ->andReturn($cachedResult);

        $this->llmManager->shouldNotReceive('driver');

        $result = $this->service->moderateContent($content);

        $this->assertEquals($cachedResult, $result);
    }

    public function test_moderate_content_multiple_providers()
    {
        Config::set('moderation.providers', ['openai', 'gemini']);
        
        // Re-instantiate service to pick up new config
        $this->service = new ContentModerationService($this->llmManager);

        $content = "Content checked by both.";

        $openaiDriver = Mockery::mock(LlmProviderInterface::class);
        $geminiDriver = Mockery::mock(LlmProviderInterface::class);

        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($openaiDriver);

        $this->llmManager->shouldReceive('driver')
            ->with('gemini')
            ->andReturn($geminiDriver);

        $openaiDriver->shouldReceive('moderate')
            ->with($content)
            ->andReturn([
                'flagged' => false,
                'categories' => ['hate' => 0.1],
                'score' => 0.1
            ]);

        $geminiDriver->shouldReceive('moderate')
            ->with($content)
            ->andReturn([
                'flagged' => true,
                'categories' => ['hate' => 0.9],
                'score' => 0.9
            ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put');

        $result = $this->service->moderateContent($content);

        // Should be flagged because Gemini flagged it
        $this->assertTrue($result['flagged']);
        $this->assertContains('openai', $result['providers']);
        $this->assertContains('gemini', $result['providers']);
    }
}
