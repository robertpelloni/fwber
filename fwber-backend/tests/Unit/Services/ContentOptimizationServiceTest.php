<?php

namespace Tests\Unit\Services;

use App\DTOs\LlmResponse;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\Services\ContentModerationService;
use App\Services\ContentOptimizationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class ContentOptimizationServiceTest extends TestCase
{
    protected $llmManager;
    protected $driver;
    protected $moderationService;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmManager = Mockery::mock(LlmManager::class);
        $this->driver = Mockery::mock(LlmProviderInterface::class);
        $this->moderationService = Mockery::mock(ContentModerationService::class);
        
        // Bind the mock moderation service to the container
        $this->app->instance(ContentModerationService::class, $this->moderationService);

        // Default config
        Config::set('content_optimization', [
            'enabled' => true,
            'providers' => ['openai'],
            'optimization_types' => [
                'engagement' => 0.4,
                'clarity' => 0.3,
                'safety' => 0.2,
                'relevance' => 0.1,
            ],
            'cache_ttl' => 3600,
        ]);

        $this->service = new ContentOptimizationService($this->llmManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_optimize_content_success()
    {
        $content = "Original content.";
        $context = ['interests' => ['tech']];
        
        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($this->driver);

        // Mock responses for each optimization type
        // Engagement
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'engagement');
            }))
            ->andReturn(new LlmResponse("Engaging content!", 'openai'));

        // Clarity
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'clarity');
            }))
            ->andReturn(new LlmResponse("Clear content.", 'openai'));

        // Safety
        $this->moderationService->shouldReceive('moderateContent')
            ->with($content)
            ->andReturn(['flagged' => false]); // Safe content, no optimization needed

        // Relevance
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'relevance');
            }))
            ->andReturn(new LlmResponse("Relevant content.", 'openai'));

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put');

        $result = $this->service->optimizeContent($content, $context);

        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('optimized', $result);
        $this->assertArrayHasKey('improvements', $result);
    }

    public function test_optimize_content_safety_flagged()
    {
        $content = "Unsafe content.";
        $context = [];

        $this->llmManager->shouldReceive('driver')
            ->with('openai')
            ->andReturn($this->driver);

        // Engagement
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'engagement');
            }))
            ->andReturn(new LlmResponse("Engaging content!", 'openai'));

        // Clarity
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'clarity');
            }))
            ->andReturn(new LlmResponse("Clear content.", 'openai'));

        // Safety - Flagged first, then optimized
        $this->moderationService->shouldReceive('moderateContent')
            ->with($content)
            ->andReturn(['flagged' => true, 'categories' => ['hate' => 0.9]]);

        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'safety');
            }))
            ->andReturn(new LlmResponse("Safe content.", 'openai'));

        $this->moderationService->shouldReceive('moderateContent')
            ->with("Safe content.")
            ->andReturn(['flagged' => false]);

        // Relevance
        $this->driver->shouldReceive('chat')
            ->with(Mockery::on(function ($messages) {
                return str_contains($messages[0]['content'], 'relevance');
            }))
            ->andReturn(new LlmResponse("Relevant content.", 'openai'));

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put');

        $result = $this->service->optimizeContent($content, $context);

        $this->assertArrayHasKey('safety', $result['improvements']);
    }

    public function test_optimize_content_cached()
    {
        $content = "Cached content.";
        $cachedResult = ['optimized' => 'Cached result'];

        Cache::shouldReceive('get')
            ->once()
            ->andReturn($cachedResult);

        $this->llmManager->shouldNotReceive('driver');

        $result = $this->service->optimizeContent($content);

        $this->assertEquals($cachedResult, $result);
    }
}
