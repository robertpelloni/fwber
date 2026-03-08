<?php

namespace Tests\Feature;

use App\Jobs\GenerateAvatar;
use App\Models\Photo;
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
        parent::setUp();
        
        // Mock config to ensure we use DALL-E for this test
        Config::set('avatar_generation.default_provider', 'dalle');
        Config::set('avatar_generation.providers.dalle.api_key', 'sk-test-key');

        // Ensure tests work without PHP Redis extension
        Config::set('cache.default', 'array');
    }

    /**
     * Helper: skip if Redis extension not loaded (needed for service-level tests
     * that resolve the full app container including Redis-backed rate limiters).
     */
    private function requireRedis(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('PHP Redis extension not installed.');
        }
    }

    /**
     * Test that the API endpoint dispatches the job and returns immediately.
     */
    public function test_api_dispatches_generation_job()
    {
        Queue::fake();
        $user = User::factory()->create();
        
        $this->actingAs($user)
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
        $this->requireRedis();
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
            'skin_tone' => 'olive',
            'love_language' => 'physical_touch',
            'relationship_style' => 'monogamous',
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
                   str_contains($prompt, 'olive skin tone') &&
                   str_contains($prompt, 'arm sleeve tattoos') &&
                   str_contains($prompt, 'nose ring piercings') &&
                   str_contains($prompt, 'wearing cyberpunk style clothing') &&
                   str_contains($prompt, 'muscular build') &&
                   str_contains($prompt, 'magnetic, affectionate energy') &&
                   str_contains($prompt, 'monogamous romantic energy') &&
                   str_contains($prompt, 'energetic, friendly expression') &&
                   str_contains($prompt, 'Gaming background theme');
        });
    }

    public function test_service_adds_identity_anchor_for_photo_based_generation()
    {
        $this->requireRedis();

        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'gender' => 'female',
            'ethnicity' => 'Latina',
            'hair_color' => 'black',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    ['url' => 'https://example.com/avatar-photo-anchor.png']
                ]
            ], 200),
            '*' => Http::response('ok', 200),
        ]);

        $service = app(AvatarGenerationService::class);
        $service->generateAvatar($user, [
            'from_photo' => true,
            'style' => 'editorial portrait',
            'sexy_boost' => true,
        ]);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.openai.com/v1/images/generations') {
                return false;
            }

            $data = $request->data();
            $prompt = $data['prompt'];

            return str_contains($prompt, 'same person as the reference photo')
                && str_contains($prompt, 'preserve recognizable facial structure')
                && str_contains($prompt, 'tasteful sexy styling');
        });
    }

    public function test_generate_from_photo_dispatches_job_with_reference_photo_metadata()
    {
        Queue::fake();

        $user = User::factory()->create();
        $photo = Photo::create([
            'user_id' => $user->id,
            'filename' => 'source.jpg',
            'original_filename' => 'source.jpg',
            'file_path' => 'photos/source.jpg',
            'mime_type' => 'image/jpeg',
            'is_primary' => false,
            'is_private' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/avatar/generate-from-photo', [
                'photo_id' => $photo->id,
                'style' => 'glamour',
                'provider' => 'replicate',
                'model' => 'custom-model',
                'lora_scale' => 0.7,
                'sexy_boost' => true,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'processing',
            ]);

        Queue::assertPushed(GenerateAvatar::class, function ($job) use ($user, $photo) {
            return $job->user->id === $user->id
                && $job->options['from_photo'] === true
                && $job->options['source_photo_id'] === $photo->id
                && $job->options['photo_path'] === $photo->file_path
                && $job->options['provider'] === 'replicate'
                && $job->options['model'] === 'custom-model';
        });
    }

    public function test_physical_traits_endpoint_returns_extended_profile_traits()
    {
        $user = User::factory()->create();

        UserProfile::create([
            'user_id' => $user->id,
            'birthdate' => '1994-01-01',
            'gender' => 'female',
            'ethnicity' => 'Asian',
            'body_type' => 'athletic',
            'hair_color' => 'black',
            'eye_color' => 'brown',
            'height_cm' => 168,
            'skin_tone' => 'golden',
            'facial_hair' => 'none',
            'breast_size' => 'medium',
            'fitness_level' => 'fit',
            'tattoos' => ['floral shoulder'],
            'piercings' => ['ear'],
            'clothing_style' => 'streetwear',
            'occupation' => 'designer',
            'personality_type' => 'ENFP',
            'love_language' => 'quality_time',
            'relationship_style' => 'monogamous',
            'interests' => ['art', 'travel'],
        ]);

        Photo::create([
            'user_id' => $user->id,
            'filename' => 'ref.jpg',
            'original_filename' => 'ref.jpg',
            'file_path' => 'photos/ref.jpg',
            'mime_type' => 'image/jpeg',
            'is_primary' => false,
            'is_private' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/avatar/physical-traits');

        $response->assertOk()
            ->assertJsonPath('traits.clothing_style', 'streetwear')
            ->assertJsonPath('traits.occupation', 'designer')
            ->assertJsonPath('traits.personality_type', 'ENFP')
            ->assertJsonPath('traits.love_language', 'quality_time')
            ->assertJsonPath('traits.relationship_style', 'monogamous')
            ->assertJsonPath('traits.interests.0', 'art')
            ->assertJsonPath('traits.tattoos.0', 'floral shoulder')
            ->assertJsonPath('photos.0.file_path', 'photos/ref.jpg');
    }

    public function test_photos_index_includes_file_path_and_metadata_for_ai_gallery(): void
    {
        $user = User::factory()->create();

        Photo::create([
            'user_id' => $user->id,
            'filename' => 'ai-avatar.png',
            'original_filename' => 'ai-avatar.png',
            'file_path' => 'avatars/ai-avatar.png',
            'mime_type' => 'image/png',
            'is_primary' => false,
            'is_private' => false,
            'sort_order' => 0,
            'metadata' => [
                'source' => 'ai',
                'provider' => 'dalle',
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/photos');

        $response->assertOk()
            ->assertJsonPath('data.0.file_path', 'avatars/ai-avatar.png')
            ->assertJsonPath('data.0.metadata.source', 'ai')
            ->assertJsonPath('data.0.metadata.provider', 'dalle');
    }

    public function test_profile_update_can_persist_avatar_url(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Avatar Tester',
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'avatar_url' => '/storage/avatars/accepted-avatar.png',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar_url' => '/storage/avatars/accepted-avatar.png',
        ]);
    }

    /**
     * Test the Service logic for option priority.
     */
    public function test_service_uses_request_options_over_profile_data()
    {
        $this->requireRedis();
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
        $this->requireRedis();
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
