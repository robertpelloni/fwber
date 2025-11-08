<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_avatar_generates_placeholder()
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
            ->postJson('/api/physical-profile/avatar/request')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['avatar_status']]);

        // Refetch profile to confirm status progressed to ready (sync queue)
        $this->withHeaders($headers)
            ->getJson('/api/physical-profile')
            ->assertStatus(200)
            ->assertJsonPath('data.avatar_status', 'ready')
            ->assertJsonPath('data.avatar_image_url', fn($v) => $v !== null);
    }

    public function test_request_avatar_without_prompt_fails()
    {
        $user = User::factory()->create();
        $headers = $this->apiHeaderFor($user);
        $this->withHeaders($headers)
            ->postJson('/api/physical-profile/avatar/request')
            ->assertStatus(422)
            ->assertJson(['error' => 'Set avatar_prompt first']);
    }

    private function apiHeaderFor(User $user): array
    {
        $plain = ApiToken::generateForUser($user, 'test');
        return [
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
        ];
    }
}
