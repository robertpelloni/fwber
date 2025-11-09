<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BulletinBoard;
use App\Services\ContentGenerationService;
use App\Services\ContentOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class ContentGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected ContentGenerationService $contentGenerationService;
    protected ContentOptimizationService $contentOptimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentGenerationService = app(ContentGenerationService::class);
        $this->contentOptimizationService = app(ContentOptimizationService::class);
    }

    #[Test]
    public function it_can_generate_profile_content_with_mock_ai_providers()
    {
        // Mock OpenAI response
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'I love hiking, photography, and trying new restaurants. Looking for someone to share adventures with!'
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Mock Gemini response
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Adventure seeker who loves exploring new places and meeting interesting people. Always up for a good conversation!']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'interests' => ['hiking', 'photography', 'travel']
        ]);

        $preferences = [
            'personality' => 'adventurous',
            'interests' => ['hiking', 'photography'],
            'goals' => 'Looking for someone to share adventures with',
            'style' => 'casual',
            'target_audience' => 'adventure lovers'
        ];

        $result = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('generation_id', $result);
        $this->assertArrayHasKey('total_providers', $result);
        
        $this->assertGreaterThan(0, count($result['suggestions']));
        
        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('id', $suggestion);
            $this->assertArrayHasKey('content', $suggestion);
            $this->assertArrayHasKey('provider', $suggestion);
            $this->assertArrayHasKey('confidence', $suggestion);
            $this->assertArrayHasKey('safety_score', $suggestion);
            $this->assertNotEmpty($suggestion['content']);
            $this->assertIsString($suggestion['id']);
        }
    }

    #[Test]
    public function it_handles_ai_provider_failures_gracefully()
    {
        // Mock OpenAI failure
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([], 500)
        ]);

        // Mock Gemini success
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Friendly person who loves good conversations and new experiences.']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $preferences = ['personality' => 'friendly'];

        $result = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertGreaterThan(0, count($result['suggestions']));
        
        // Should have at least one suggestion from Gemini
        $geminiSuggestions = array_filter($result['suggestions'], fn($s) => $s['provider'] === 'gemini');
        $this->assertGreaterThan(0, count($geminiSuggestions));
    }

    #[Test]
    public function it_caches_generation_results()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Cached content'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $preferences = ['personality' => 'test'];

        // First call
        $result1 = $this->contentGenerationService->generateProfileContent($user, $preferences);
        
        // Second call should use cache
        $result2 = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertEquals($result1, $result2);
        
        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_generates_post_suggestions_for_bulletin_board()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Anyone up for a coffee meetup this weekend?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $board = BulletinBoard::factory()->create([
            'name' => 'Downtown Coffee Lovers',
            'description' => 'A place for coffee enthusiasts to connect'
        ]);

        $context = [
            'location' => ['latitude' => 40.7128, 'longitude' => -74.0060],
            'time' => 'weekend',
            'topics' => ['coffee', 'meetup']
        ];

        $result = $this->contentGenerationService->generatePostSuggestions($board, $user, $context);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertGreaterThan(0, count($result['suggestions']));
    }

    #[Test]
    public function it_generates_conversation_starters()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What\'s your favorite way to spend a weekend?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $user = User::factory()->create();
        $context = [
            'type' => 'casual',
            'target_user' => ['interests' => ['travel', 'food']],
            'previous_messages' => []
        ];

        $result = $this->contentGenerationService->generateConversationStarters($user, $context);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertGreaterThan(0, count($result['suggestions']));
    }

    #[Test]
    public function it_optimizes_content_with_multi_ai_consensus()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'I love hiking and photography. Looking for someone to share adventures with!'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $content = 'I like hiking and taking pictures. Want to hang out?';
        $context = ['type' => 'profile'];

        $result = $this->contentOptimizationService->optimizeContent($content, $context);

        $this->assertArrayHasKey('original_content', $result);
        $this->assertArrayHasKey('optimized_version', $result);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('suggestions', $result);
        
        $this->assertEquals($content, $result['original_content']);
        $this->assertNotEquals($content, $result['optimized_version']);
        $this->assertNotEmpty($result['optimized_version']);
    }

    #[Test]
    public function it_calculates_confidence_scores_correctly()
    {
        $service = new \ReflectionClass($this->contentGenerationService);
        $method = $service->getMethod('calculateConfidence');
        $method->setAccessible(true);

        $goodContent = 'This is a well-written, engaging profile that showcases personality and interests effectively.';
        $badContent = 'bad.';

        $goodScore = $method->invoke($this->contentGenerationService, $goodContent);
        $badScore = $method->invoke($this->contentGenerationService, $badContent);

        $this->assertGreaterThan($badScore, $goodScore);
        $this->assertGreaterThanOrEqual(0, $goodScore);
        $this->assertLessThanOrEqual(1, $goodScore);
    }

    #[Test]
    public function it_handles_empty_user_data_gracefully()
    {
        $user = User::factory()->create([
            'interests' => null,
            'name' => 'Test User'
        ]);

        $preferences = [];

        $result = $this->contentGenerationService->generateProfileContent($user, $preferences);

        $this->assertArrayHasKey('suggestions', $result);
        // Should still generate content even with minimal data
        $this->assertGreaterThan(0, count($result['suggestions']));
    }

    #[Test]
    public function it_validates_feedback_submission()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test valid feedback
        $response = $this->postJson('/api/content-generation/feedback', [
            'suggestion_id' => '123e4567-e89b-12d3-a456-426614174000',
            'content_type' => 'profile',
            'rating' => 5,
            'feedback' => 'Great suggestion!',
            'improvements' => ['more specific', 'add humor']
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Feedback submitted successfully'
        ]);

        // Test invalid feedback
        $response = $this->postJson('/api/content-generation/feedback', [
            'suggestion_id' => 'invalid-uuid',
            'content_type' => 'invalid_type',
            'rating' => 10, // Invalid rating
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'messages'
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_content_generation()
    {
        $response = $this->postJson('/api/content-generation/profile', [
            'personality' => 'friendly'
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_handles_rate_limiting()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Make multiple requests quickly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/content-generation/profile', [
                'personality' => 'test'
            ]);
            
            if ($i < 5) {
                $response->assertStatus(200);
            } else {
                // Should be rate limited
                $response->assertStatus(429);
            }
        }
    }
}
