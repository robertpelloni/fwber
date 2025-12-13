<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Carbon\Carbon;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_streak_starts_at_one()
    {
        $user = \App\Models\User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson(['current_streak' => 1]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_streak' => 1,
        ]);
    }

    public function test_streak_increments_on_consecutive_days()
    {
        $user = \App\Models\User::factory()->create([
            'current_streak' => 1,
            'last_active_at' => Carbon::yesterday(),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson(['current_streak' => 2]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_streak' => 2,
        ]);
    }

    public function test_streak_resets_after_missed_day()
    {
        $user = \App\Models\User::factory()->create([
            'current_streak' => 5,
            'last_active_at' => Carbon::now()->subDays(2),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson(['current_streak' => 1]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_streak' => 1,
        ]);
    }

    public function test_streak_does_not_increment_twice_same_day()
    {
        $user = \App\Models\User::factory()->create([
            'current_streak' => 5,
            'last_active_at' => Carbon::now(),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson(['current_streak' => 5]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_streak' => 5,
        ]);
    }
}
