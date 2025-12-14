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
                ->once()
                ->andReturn('Look at you, a coding hiker. How original. Do you debug trails too?');
        });

        $response = $this->postJson('/api/wingman/roast');

        $response->assertStatus(200)
            ->assertJson([
                'roast' => 'Look at you, a coding hiker. How original. Do you debug trails too?'
            ]);
    }
}
