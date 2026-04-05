<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
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

    public function test_public_roast_endpoint_falls_back_cleanly_when_driver_throws_non_exception_throwables(): void
    {
        $provider = Mockery::mock(LlmProviderInterface::class);
        $provider->shouldReceive('chat')->once()->andThrow(new \Error('driver exploded'));

        $manager = Mockery::mock(LlmManager::class);
        $manager->shouldReceive('driver')->once()->andReturn($provider);

        $this->app->instance(AiWingmanService::class, new AiWingmanService($manager));

        $response = $this->postJson('/api/public/roast', [
            'name' => 'Mia',
            'job' => 'Night-shift nurse',
            'trait' => 'Always early',
            'mode' => 'roast',
        ]);

        $response->assertOk();
        $response->assertJsonPath('is_preview', true);
        $response->assertJsonPath('roast', "You broke the roast machine! That's how un-roastable you are. (Try again later)");
    }
}
