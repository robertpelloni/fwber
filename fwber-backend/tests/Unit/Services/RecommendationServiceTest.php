<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecommendationService;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\DTOs\LlmResponse;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\TelemetryEvent;
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

        // We use the real service now, but mock LLM
        $this->service = new RecommendationService($this->llmManager);
    }

    public function testFindSimilarUsers()
    {
        // Create target user
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'interests' => ['coding', 'music', 'hiking'],
            'birthdate' => '1990-01-01',
        ]);

        // Create similar user
        $similarUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $similarUser->id,
            'interests' => ['coding', 'music', 'gaming'], // 2/4 overlap (coding, music) vs (coding, music, hiking, gaming) -> 0.5
            'birthdate' => '1992-01-01',
        ]);

        // Create dissimilar user
        $dissimilarUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $dissimilarUser->id,
            'interests' => ['cooking', 'reading'],
            'birthdate' => '1985-01-01',
        ]);

        // Create liked content for similar user via Telemetry
        TelemetryEvent::create([
            'user_id' => $similarUser->id,
            'event' => 'like_content',
            'payload' => [
                'content_id' => 1,
                'content_type' => 'bulletin_message',
                'title' => 'Coding Workshop',
                'description' => 'Learn Laravel'
            ],
            'recorded_at' => now(),
        ]);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('findSimilarUsers');
        $method->setAccessible(true);

        $profile = [
            'id' => $user->id,
            'interests' => ['coding', 'music', 'hiking']
        ];

        $result = $method->invoke($this->service, $profile);

        $this->assertCount(1, $result);
        $this->assertEquals($similarUser->id, $result[0]['id']);
        $this->assertGreaterThan(0.3, $result[0]['similarity']);
        $this->assertEquals('Coding Workshop', $result[0]['liked_content'][0]['title']);
    }

    public function testCalculateContentScore()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateContentScore');
        $method->setAccessible(true);

        $content = [
            'title' => 'Laravel Workshop',
            'description' => 'Learn PHP and Coding'
        ];

        $profile = [
            'interests' => ['coding', 'php'],
            'location' => ['name' => 'New York']
        ];

        $score = $method->invoke($this->service, $content, $profile);

        // Base 0.5 + 0.1 (coding) + 0.1 (php) = 0.7
        $this->assertEquals(0.7, $score);
    }

    public function testCalculateCollaborativeScore()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCollaborativeScore');
        $method->setAccessible(true);

        $similarUser = ['similarity' => 0.8];
        $content = ['created_at' => now()->subHours(1)->toDateTimeString()];

        $score = $method->invoke($this->service, $similarUser, $content);

        // Freshness ~1.0. Score = 0.8 * 1.0 + 0.2 = 1.0
        $this->assertEqualsWithDelta(1.0, $score, 0.01);
    }

    public function testGetTrendingTopics()
    {
        // Create messages with recurring keywords
        $user = User::factory()->create();
        $board = \App\Models\BulletinBoard::factory()->create();

        \App\Models\BulletinMessage::create([
            'user_id' => $user->id,
            'bulletin_board_id' => $board->id,
            'content' => 'I love coding with Laravel framework',
        ]);

        \App\Models\BulletinMessage::create([
            'user_id' => $user->id,
            'bulletin_board_id' => $board->id,
            'content' => 'Laravel is a great framework for coding',
        ]);

        \App\Models\BulletinMessage::create([
            'user_id' => $user->id,
            'bulletin_board_id' => $board->id,
            'content' => 'Coding in PHP is fun',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTrendingTopics');
        $method->setAccessible(true);

        $topics = $method->invoke($this->service);

        // 'coding' appears 3 times, 'laravel' 2 times, 'framework' 2 times
        $this->assertContains('coding', $topics);
        $this->assertContains('laravel', $topics);
        $this->assertContains('framework', $topics);
    }
}
