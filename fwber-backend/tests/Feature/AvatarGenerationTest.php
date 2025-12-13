<?php

namespace Tests\Feature;

use App\Jobs\GenerateAvatar;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AvatarGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AvatarGeneratedNotification;
use Tests\TestCase;

class AvatarGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $user = User::factory()->create();
        $headers = $this->apiHeaderFor($user);

        $this->withHeaders($headers)
            ->putJson('/api/physical-profile', [
                'avatar_prompt' => 'Stylized portrait in synthwave neon style'
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['avatar_prompt']]);

        $this->withHeaders($headers)
            ->postJson('/api/physical-profile/avatar/request', ['style' => 'anime'])
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['avatar_status']]);

        // Refetch profile to confirm status progressed to ready (sync queue)
        $this->withHeaders($headers)
            ->getJson('/api/physical-profile')
            ->assertStatus(200)
            ->assertJsonPath('data.avatar_status', 'ready')
            ->assertJsonPath('data.avatar_image_url', fn($v) => $v !== null);
        parent::setUp();
        
        // Mock config to ensure we use DALL-E for this test
        Config::set('avatar_generation.default_provider', 'dalle');
        Config::set('avatar_generation.providers.dalle.api_key', 'sk-test-key');
    }

    /**
     * Test that the API endpoint dispatches the job and returns immediately.
     */
    public function test_api_dispatches_generation_job()
    {
        Queue::fake();
        $user = User::factory()->create();
        $headers = $this->apiHeaderFor($user);
        $this->withHeaders($headers)
            ->postJson('/api/physical-profile/avatar/request', ['style' => 'anime'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Set avatar_prompt first']);

        $response = $this->actingAs($user)
            ->postJson('/api/avatar/generate', [
                'style' => 'digital art',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'processing'
            ]);

        Queue::assertPushed(GenerateAvatar::class, function ($job) use ($user) {
            return $job->user->id === $user->id &&
                   $job->options['style'] === 'digital art';
        });
    }

    /**
     * Test the Service logic for prompt generation.
     */
    public function test_service_generates_prompt_with_detailed_attributes()
    {
        $user = User::factory()->create();
        
        // Create a detailed profile
        UserProfile::create([
            'user_id' => $user->id,
            'birthdate' => '1995-01-01', // 30 years old -> adult
            'gender' => 'female',
            'ethnicity' => 'Asian',
            'hair_color' => 'purple',
            'eye_color' => 'green',
            'body_type' => 'athletic',
            'height_cm' => 175, // Tall
            'tattoos' => ['arm sleeve'],
            'piercings' => ['nose ring'],
            'clothing_style' => 'cyberpunk',
            'fitness_level' => 'muscular',
            'personality_type' => 'ENTP', // Extrovert -> energetic
            'interests' => ['Gaming', 'Coding'],
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    ['url' => 'https://example.com/avatar.png']
                ]
            ], 200),
            '*' => Http::response('ok', 200),
        ]);

        // Instantiate service directly
        $service = app(AvatarGenerationService::class);
        $result = $service->generateAvatar($user, ['style' => 'digital art']);

        $this->assertTrue($result['success']);

        // Verify the prompt sent to OpenAI
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.openai.com/v1/images/generations') {
                return false;
            }

            $data = $request->data();
            $prompt = $data['prompt'];

            return str_contains($prompt, 'digital art') &&
                   str_contains($prompt, 'adult person') &&
                   str_contains($prompt, 'woman') &&
                   str_contains($prompt, 'Asian') &&
                   str_contains($prompt, 'purple hair') &&
                   str_contains($prompt, 'green eyes') &&
                   str_contains($prompt, 'athletic body type') &&
                   str_contains($prompt, 'visible tattoos') &&
                   str_contains($prompt, 'piercings') &&
                   str_contains($prompt, 'wearing cyberpunk style clothing') &&
                   str_contains($prompt, 'muscular build') &&
                   str_contains($prompt, 'energetic, friendly expression') &&
                   str_contains($prompt, 'Gaming background theme');
        });
    }

    /**
     * Test the Service logic for option priority.
     */
    public function test_service_uses_request_options_over_profile_data()
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'gender' => 'male',
            'hair_color' => 'brown',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    ['url' => 'https://example.com/avatar.png']
                ]
            ], 200),
            '*' => Http::response('ok', 200),
        ]);

        $service = app(AvatarGenerationService::class);
        $service->generateAvatar($user, [
            'gender' => 'female', // Override gender
            'hair_color' => 'pink', // Override hair
        ]);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.openai.com/v1/images/generations') {
                return false;
            }
            $prompt = $request->data()['prompt'];
            return str_contains($prompt, 'woman') && 
                   str_contains($prompt, 'pink hair') &&
                   !str_contains($prompt, 'brown hair');
        });
    }

    /**
     * Test the Service logic for unsafe content rejection.
     */
    public function test_service_rejects_unsafe_generated_avatar()
    {
        Config::set('features.media_analysis', true);
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id, 'gender' => 'male']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    ['url' => 'https://example.com/unsafe-avatar.png']
                ]
            ], 200),
            'https://example.com/unsafe-avatar.png' => Http::response('unsafe-image-content', 200),
        ]);

        // Mock MediaAnalysisInterface
        $mockAnalysis = \Mockery::mock(\App\Services\MediaAnalysis\MediaAnalysisInterface::class);
        $mockAnalysis->shouldReceive('analyze')
            ->once()
            ->andReturn(new \App\Services\MediaAnalysis\MediaAnalysisResult(
                false, // safe
                [],
                ['Explicit Content'], // unsafe reasons
                0.9,
                ['source' => 'mock']
            ));
        
        $this->app->instance(\App\Services\MediaAnalysis\MediaAnalysisInterface::class, $mockAnalysis);

        $service = app(AvatarGenerationService::class);
        $result = $service->generateAvatar($user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('unsafe', $result['error']);
    }

    public function test_can_fetch_providers_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/avatar/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'providers' => [
                    'dalle',
                    'gemini',
                    'replicate'
                ]
            ]);
    }

    /**
     * Test the full Job execution flow (Service -> DB -> Notification).
     */
    public function test_job_execution_saves_photo_and_notifies()
    {
        Notification::fake();
        $user = User::factory()->create();
        Config::set('avatar_generation.providers.replicate.api_token', 'test-token');

        // Mock Replicate API flow
        Http::fake([
            // 1. Prediction Request
            'api.replicate.com/v1/predictions' => Http::response([
                'id' => 'pred_123',
                'status' => 'starting',
            ], 201),
            
            // 2. Polling Status (Succeeded)
            'api.replicate.com/v1/predictions/pred_123' => Http::response([
                'id' => 'pred_123',
                'status' => 'succeeded',
                'output' => ['https://replicate.com/output.png']
            ], 200),

            // 3. Download Image
            'replicate.com/output.png' => Http::response('fake-image-content', 200),
        ]);

        // Manually execute the job
        $job = new GenerateAvatar($user, [
            'provider' => 'replicate',
            'model' => 'custom-model-v1',
            'lora_scale' => 0.8,
            'style' => 'cyberpunk',
        ]);
        
        $job->handle(app(AvatarGenerationService::class));

        // Verify Replicate Request contained custom options
        Http::assertSent(function ($request) {
            if ($request->url() === 'https://api.replicate.com/v1/predictions') {
                $data = $request->data();
                return $data['version'] === 'custom-model-v1' &&
                       $data['input']['lora_scale'] === 0.8;
            }
            return true;
        });

        // Verify Photo was created
        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
            'mime_type' => 'image/png',
        ]);

        // Verify Metadata
        $photo = \App\Models\Photo::where('user_id', $user->id)->latest()->first();
        $this->assertEquals('ai', $photo->metadata['source']);
        $this->assertEquals('replicate', $photo->metadata['provider']);
        $this->assertEquals('custom-model-v1', $photo->metadata['model']);
        $this->assertEquals('cyberpunk', $photo->metadata['style']);

        // Verify Notification
        Notification::assertSentTo(
            [$user],
            AvatarGeneratedNotification::class
        );
    }
}
