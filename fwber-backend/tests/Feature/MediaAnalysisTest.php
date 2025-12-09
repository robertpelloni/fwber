<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_analysis_endpoint()
    {
        \Illuminate\Support\Facades\Config::set('features.media_analysis', true);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/media/analyze', [
            'url' => 'https://example.com/image.jpg',
            'type' => 'image',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'safe',
                    'labels',
                    'confidence',
                ],
            ]);
    }

    public function test_media_analysis_unsafe_mock()
    {
        \Illuminate\Support\Facades\Config::set('features.media_analysis', true);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/media/analyze', [
            'url' => 'https://example.com/unsafe_image.jpg',
            'type' => 'image',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.safe', false);
    }
}
