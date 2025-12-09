<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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
    }

    public function test_generates_avatar_prompt_with_detailed_attributes()
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
            '*' => Http::response('ok', 200), // Catch-all for image download
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/avatar/generate', [
                'style' => 'digital art',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify the prompt sent to OpenAI
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.openai.com/v1/images/generations') {
                return false;
            }

            $data = $request->data();
            $prompt = $data['prompt'];

            // Check for all the attributes we expect in the prompt
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
                   str_contains($prompt, 'energetic, friendly expression') && // From ENTP
                   str_contains($prompt, 'Gaming background theme'); // From interests
        });
    }

    public function test_uses_request_options_over_profile_data()
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

        $this->actingAs($user)
            ->postJson('/api/avatar/generate', [
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

    public function test_rejects_unsafe_generated_avatar()
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

        // Mock MediaAnalysisInterface to return unsafe
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

        $response = $this->actingAs($user)
            ->postJson('/api/avatar/generate');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => 'Generated avatar was flagged as unsafe: Explicit Content'
            ]);
    }
}
