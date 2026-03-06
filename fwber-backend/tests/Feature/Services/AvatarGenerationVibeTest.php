<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProximityArtifact;
use App\Services\AvatarGenerationService;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AvatarGenerationVibeTest extends TestCase
{
    use RefreshDatabase;

    private AvatarGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mediaAnalysisMock = Mockery::mock(MediaAnalysisInterface::class);
        $this->app->instance(MediaAnalysisInterface::class, $mediaAnalysisMock);
        
        $this->service = app(AvatarGenerationService::class);
    }

    public function test_it_injects_energetic_vibe_into_prompt()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // Create energetic posts
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Having fun at the club tonight!']);
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Epic party with friends']);

        // Use Reflection to test the private buildPrompt method
        $reflection = new \ReflectionClass(AvatarGenerationService::class);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service, $user, []);

        $this->assertStringContainsString('energetic aura', $prompt);
        $this->assertStringContainsString('vibrant warm lighting', $prompt);
    }

    public function test_it_injects_relaxed_vibe_into_prompt()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // Create relaxed posts
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Reading a book with coffee alone']);
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Cozy rainy morning at home']);

        $reflection = new \ReflectionClass(AvatarGenerationService::class);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service, $user, []);

        $this->assertStringContainsString('relaxed aura', $prompt);
        $this->assertStringContainsString('soft warm lighting', $prompt);
    }

    public function test_it_injects_edgy_vibe_into_prompt()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // Create edgy posts
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'So mad and angry right now']);
        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Dark midnight thoughts alone']);

        $reflection = new \ReflectionClass(AvatarGenerationService::class);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service, $user, []);

        $this->assertStringContainsString('intense aura', $prompt);
        $this->assertStringContainsString('dramatic cinematic lighting', $prompt);
    }

    public function test_it_falls_back_to_mbti_if_no_posts()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'personality_type' => 'INTP']);

        // No posts created

        $reflection = new \ReflectionClass(AvatarGenerationService::class);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service, $user, []);

        $this->assertStringNotContainsString('energetic aura', $prompt);
        $this->assertStringContainsString('calm, thoughtful expression', $prompt); // From the 'I' in INTP
    }

    public function test_it_returns_clean_vibe_in_generation_result()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        ProximityArtifact::factory()->create(['user_id' => $user->id, 'type' => 'board_post', 'content' => 'Having fun at the club tonight!']);

        // Force 'testing' environment and mock the HTTP request to OpenAI
        app()->detectEnvironment(fn() => 'testing');
        config(['avatar_generation.providers.dalle.api_key' => 'fake-key']);
        config(['features.media_analysis' => true]);

        $mediaAnalysisMock = app(MediaAnalysisInterface::class);
        $resultObj = new \App\Services\MediaAnalysis\MediaAnalysisResult(true);
        $mediaAnalysisMock->shouldReceive('analyze')->once()->andReturn($resultObj);
        
        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/v1/images/generations' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    ['url' => 'https://example.com/fake-image.png']
                ]
            ], 200),
            'example.com/*' => \Illuminate\Support\Facades\Http::response('fake-image-data', 200)
        ]);

        $result = $this->service->generateAvatar($user, ['provider' => 'dalle']);

        if (!$result['success']) {
            dump($result);
        }

        $this->assertTrue($result['success']);
        $this->assertEquals('energetic', $result['vibe']);
    }
}
