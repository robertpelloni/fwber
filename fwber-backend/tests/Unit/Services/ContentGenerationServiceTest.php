<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ContentGenerationService;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\Models\User;
use Mockery;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

use App\DTOs\LlmResponse;

class ContentGenerationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_profile_content_uses_llm_manager()
    {
        // Mock User
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('getAttribute')->with('name')->andReturn('Test User');
        $user->shouldReceive('getAttribute')->with('age')->andReturn(25);
        $user->shouldReceive('getAttribute')->with('interests')->andReturn(['coding', 'ai']);
        $user->shouldReceive('getAttribute')->with('latitude')->andReturn(0.0);
        $user->shouldReceive('getAttribute')->with('longitude')->andReturn(0.0);
        $user->shouldReceive('getAttribute')->with('created_at')->andReturn(now());
        // Handle isset checks (?? operator)
        $user->shouldReceive('offsetExists')->andReturn(true);
        
        // Mock BulletinMessage alias to avoid DB calls
        $mockMessage = Mockery::mock('alias:App\Models\BulletinMessage');
        $mockMessage->shouldReceive('where->get')->andReturn(collect([]));
        $mockMessage->shouldReceive('where->count')->andReturn(0);

        // Mock LlmManager response
        $responseObj = new LlmResponse(
            content: 'Generated profile bio',
            provider: 'claude'
        );

        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->andReturn($responseObj);

        $mockLlmManager = Mockery::mock(LlmManager::class);
        // The service iterates providers. We'll configure it to use 'claude' and mock that.
        $mockLlmManager->shouldReceive('driver')
            ->with('claude')
            ->andReturn($mockDriver);

        Config::set('content_generation.routing.profile', ['claude']);
        Config::set('content_generation.models.claude', 'claude-3-5-sonnet-20241022');
        Config::set('content_generation.max_tokens', 100);
        Config::set('content_generation.temperature', 0.7);
        Config::set('content_generation.cache_ttl', 3600); // Add this line

        $service = new ContentGenerationService($mockLlmManager);
        
        $result = $service->generateProfileContent($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertEquals('Generated profile bio', $result['suggestions'][0]['content']);
    }
}

