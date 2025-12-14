<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiWingmanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class AiProfileRoasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_roast_profile()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'I love hiking and coding.',
            'interests' => ['hiking', 'coding'],
            'occupation' => 'Software Engineer',
            'birthdate' => now()->subYears(25),
        ]);

        $this->actingAs($user);

        // Mock AiWingmanService
        $this->mock(AiWingmanService::class, function ($mock) {
            $mock->shouldReceive('roastProfile')
                ->with(Mockery::type(User::class), 'roast')
                ->once()
                ->andReturn('Look at you, a coding hiker. How original. Do you debug trails too?');
        });

        $response = $this->postJson('/api/wingman/roast');

        $response->assertStatus(200)
            ->assertJson([
                'roast' => 'Look at you, a coding hiker. How original. Do you debug trails too?'
            ]);
    }

    public function test_can_hype_profile()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'I love hiking and coding.',
        ]);

        $this->actingAs($user);

        // Mock AiWingmanService
        $this->mock(AiWingmanService::class, function ($mock) {
            $mock->shouldReceive('roastProfile')
                ->with(Mockery::type(User::class), 'hype')
                ->once()
                ->andReturn('You are the absolute best! Coding genius and mountain conqueror!');
        });

        $response = $this->postJson('/api/wingman/roast', ['mode' => 'hype']);

        $response->assertStatus(200)
            ->assertJson([
                'roast' => 'You are the absolute best! Coding genius and mountain conqueror!'
            ]);
    }

    public function test_can_check_vibe()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'I love hiking and coding.',
        ]);

        $this->actingAs($user);

        // Mock AiWingmanService
        $this->mock(AiWingmanService::class, function ($mock) {
            $mock->shouldReceive('checkVibe')
                ->with(Mockery::type(User::class))
                ->once()
                ->andReturn([
                    'green_flags' => ['Passionate', 'Active'],
                    'red_flags' => ['Might talk about code too much']
                ]);
        });

        $response = $this->getJson('/api/wingman/vibe-check');

        $response->assertStatus(200)
            ->assertJson([
                'green_flags' => ['Passionate', 'Active'],
                'red_flags' => ['Might talk about code too much']
            ]);
    }
}
