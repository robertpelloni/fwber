<?php

namespace Tests\Feature;

use App\Models\SafeWalk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SafetyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_active_walk()
    {
        $user = User::factory()->create();
        $walk = SafeWalk::create([
            'user_id' => $user->id,
            'status' => 'active',
            'start_lat' => 40.7128,
            'start_lng' => -74.0060,
            'current_lat' => 40.7128,
            'current_lng' => -74.0060,
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/safety/walk/active')
            ->assertOk()
            ->assertJsonPath('walk.id', $walk->id);
    }

    public function test_active_walk_returns_null_when_safety_tables_are_missing()
    {
        $user = User::factory()->create();
        Schema::dropIfExists('safe_walks');

        $this->actingAs($user)
            ->getJson('/api/safety/walk/active')
            ->assertOk()
            ->assertJson([
                'walk' => null,
            ]);
    }
}
