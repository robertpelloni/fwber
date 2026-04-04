<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AiWingmanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiWingmanRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_roast_endpoint_returns_preview_payload(): void
    {
        $mock = Mockery::mock(AiWingmanService::class);
        $mock->shouldReceive('roastGeneric')
            ->once()
            ->with('Mia', 'Night-shift nurse', 'Always early', 'roast')
            ->andReturn('A lovingly ruthless roast.');

        $this->app->instance(AiWingmanService::class, $mock);

        $response = $this->postJson('/api/public/roast', [
            'name' => 'Mia',
            'job' => 'Night-shift nurse',
            'trait' => 'Always early',
            'mode' => 'roast',
        ]);

        $response->assertOk();
        $response->assertJson([
            'roast' => 'A lovingly ruthless roast.',
            'is_preview' => true,
        ]);
    }

    public function test_authenticated_roast_endpoint_returns_shareable_payload(): void
    {
        $user = User::factory()->create();

        $mock = Mockery::mock(AiWingmanService::class);
        $mock->shouldReceive('roastProfile')
            ->once()
            ->withArgs(function ($resolvedUser, $mode) use ($user) {
                return $resolvedUser->id === $user->id && $mode === 'hype';
            })
            ->andReturn('You are pure main-character energy.');

        $this->app->instance(AiWingmanService::class, $mock);

        $response = $this->actingAs($user)->postJson('/api/wingman/roast', [
            'mode' => 'hype',
        ]);

        $response->assertOk();
        $response->assertJsonPath('roast', 'You are pure main-character energy.');
    }
}
