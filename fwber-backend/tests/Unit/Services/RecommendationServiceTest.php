<?php

namespace Tests\Unit\Services;

use App\Models\BulletinBoard;
use App\Models\Event;
use App\Models\Friend;
use App\Models\TelemetryEvent;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\Services\LocalPulseRankingService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

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
        $this->service = new RecommendationService($this->llmManager, new LocalPulseRankingService);
    }

    public function test_find_similar_users()
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
                'description' => 'Learn Laravel',
            ],
            'recorded_at' => now(),
        ]);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('findSimilarUsers');
        $method->setAccessible(true);

        $profile = [
            'id' => $user->id,
            'interests' => ['coding', 'music', 'hiking'],
        ];

        $result = $method->invoke($this->service, $profile);

        $this->assertCount(1, $result);
        $this->assertEquals($similarUser->id, $result[0]['id']);
        $this->assertGreaterThan(0.3, $result[0]['similarity']);
        $this->assertEquals('Coding Workshop', $result[0]['liked_content'][0]['title']);
    }

    public function test_calculate_content_score()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateContentScore');
        $method->setAccessible(true);

        $content = [
            'title' => 'Laravel Workshop',
            'description' => 'Learn PHP and Coding',
        ];

        $profile = [
            'interests' => ['coding', 'php'],
            'location' => ['name' => 'New York'],
        ];

        $score = $method->invoke($this->service, $content, $profile);

        // Base 0.5 + 0.1 (coding) + 0.1 (php) = 0.7
        $this->assertEquals(0.7, $score);
    }

    public function test_calculate_collaborative_score()
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

    public function test_get_trending_topics()
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

    public function test_calculate_engagement_score_counts_recent_attending_events_using_starts_at()
    {
        $user = User::factory()->create();

        $recentEvent = Event::create([
            'title' => 'Recent Event',
            'description' => 'Recent recommendation signal',
            'type' => 'social',
            'location_name' => 'Detroit',
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(5)->addHours(2),
            'created_by_user_id' => $user->id,
            'status' => 'active',
        ]);

        $oldEvent = Event::create([
            'title' => 'Old Event',
            'description' => 'Outside recommendation window',
            'type' => 'social',
            'location_name' => 'Detroit',
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'starts_at' => now()->subDays(45),
            'ends_at' => now()->subDays(45)->addHours(2),
            'created_by_user_id' => $user->id,
            'status' => 'completed',
        ]);

        $user->attendingEvents()->attach([$recentEvent->id, $oldEvent->id], ['status' => 'attending']);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateEngagementScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->service, $user);

        $this->assertGreaterThan(0.09, $score);
    }

    public function test_location_recommendations_use_request_context_coordinates()
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'birthdate' => '1990-01-01',
        ]);

        BulletinBoard::factory()->create([
            'name' => 'Nearby Board',
            'center_lat' => 42.3314,
            'center_lng' => -83.0458,
            'is_active' => true,
        ]);

        $recommendations = $this->service->getRecommendations($user->id, [
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'radius' => 5000,
        ], ['location']);

        $this->assertCount(1, $recommendations);
        $this->assertSame('location', $recommendations[0]['type']);
        $this->assertSame('Nearby Board', $recommendations[0]['content']['name']);
    }

    public function test_recommendations_include_scene_signals_from_followed_topics(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'birthdate' => '1990-01-01',
            'interests' => ['coffee', 'design'],
        ]);

        $topic = Topic::create([
            'slug' => 'warehouse-nights',
            'label' => 'Warehouse Nights',
            'description' => 'Late nights and underground rooms.',
            'emoji' => '🌃',
            'category' => 'culture',
            'aliases' => ['warehouse', 'nightlife'],
            'is_featured' => true,
            'sort_order' => 99,
        ]);

        $user->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        BulletinBoard::factory()->create([
            'name' => 'Warehouse Nights Board',
            'description' => 'Coffee pregame before the warehouse set.',
            'center_lat' => 42.3314,
            'center_lng' => -83.0458,
            'is_active' => true,
        ]);

        $recommendations = $this->service->getRecommendations($user->id, [
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'radius' => 5000,
        ], ['location']);

        $this->assertCount(1, $recommendations);
        $this->assertArrayHasKey('scene_signals', $recommendations[0]);
        $sceneSignals = $recommendations[0]['scene_signals'];
        $this->assertIsArray($sceneSignals['matched_topics']);
        $this->assertContains('coffee', $sceneSignals['matched_tags']);
        $this->assertContains('warehouse', $sceneSignals['matched_tags']);
        $this->assertGreaterThan(0, $recommendations[0]['scene_signals']['score_boost']);
    }

    public function test_apply_trust_aware_ranking_prioritizes_trusted_scene_aligned_recommendations(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        Friend::factory()->create([
            'user_id' => $viewer->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friend->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        $recommendations = [
            [
                'id' => 'stranger-post',
                'type' => 'collaborative',
                'content' => [
                    'id' => 101,
                    'title' => 'Generic update',
                    'description' => 'Something new nearby.',
                    'creator_id' => $stranger->id,
                    'created_at' => now()->subMinute()->toISOString(),
                ],
                'score' => 0.72,
                'reason' => 'Users like you enjoyed this',
            ],
            [
                'id' => 'friend-post',
                'type' => 'collaborative',
                'content' => [
                    'id' => 102,
                    'title' => 'Warehouse coffee set',
                    'description' => 'Coffee meetup before the warehouse set tonight.',
                    'creator_id' => $friend->id,
                    'created_at' => now()->subMinutes(10)->toISOString(),
                ],
                'score' => 0.48,
                'reason' => 'Users like you enjoyed this',
                'scene_signals' => [
                    'headline' => 'Scene match on Warehouse Nights',
                    'matched_topics' => [],
                    'matched_tags' => ['coffee', 'warehouse'],
                    'score_boost' => 0.14,
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('applyTrustAwareRanking');
        $method->setAccessible(true);

        $ranked = $method->invoke($this->service, $viewer, $recommendations);

        $this->assertSame('friend-post', $ranked[0]['id']);
        $this->assertGreaterThan($ranked[1]['_ranking_score'], $ranked[0]['_ranking_score']);
    }
}
