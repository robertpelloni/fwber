<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\ContentGenerationService;
use Tests\Traits\RefreshDatabaseSilently;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;

class ContentGenerationRoutingTest extends TestCase
{
    use RefreshDatabaseSilently;

    protected ContentGenerationService $contentGenerationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentGenerationService = app(ContentGenerationService::class);
    }

    #[Test]
    public function it_uses_claude_as_primary_provider_for_profiles()
    {
        Config::set('content_generation.routing.profile', ['claude', 'openai']);
        
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    [
                        'text' => 'Claude generated profile content'
                    ]
                ]
            ], 200),
            'api.openai.com/*' => Http::response(['choices' => []], 500) // Fail OpenAI to ensure it's not used or fallback works
        ]);

        $user = User::factory()->create();
        $preferences = ['personality' => 'creative'];

        $result = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertNotEmpty($result['suggestions']);
        $this->assertEquals('claude', $result['suggestions'][0]['provider']);
        $this->assertEquals('Claude generated profile content', $result['suggestions'][0]['content']);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.anthropic.com');
        });
    }

    #[Test]
    public function it_falls_back_to_secondary_provider_when_primary_fails()
    {
        Config::set('content_generation.routing.profile', ['claude', 'openai']);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([], 500), // Claude fails
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'OpenAI fallback content'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $preferences = ['personality' => 'resilient'];

        $result = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertNotEmpty($result['suggestions']);
        $this->assertEquals('openai', $result['suggestions'][0]['provider']);
        $this->assertEquals('OpenAI fallback content', $result['suggestions'][0]['content']);
    }

    #[Test]
    public function it_respects_custom_routing_for_different_content_types()
    {
        // Configure Post Suggestions to use Gemini first
        Config::set('content_generation.routing.post_suggestions', ['gemini', 'openai']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Gemini post suggestion']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $board = \App\Models\BulletinBoard::factory()->create();

        $result = $this->contentGenerationService->generatePostSuggestions($board, $user);

        $this->assertNotEmpty($result['suggestions']);
        $this->assertEquals('gemini', $result['suggestions'][0]['provider']);
    }
}
