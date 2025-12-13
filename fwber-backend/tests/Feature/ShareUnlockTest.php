<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShareUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_unlock_profile_by_sharing()
    {
        $user = \App\Models\User::factory()->create();
        $target = \App\Models\User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/share-unlock', [
            'target_profile_id' => $target->id,
            'platform' => 'copy_link',
        ]);

        $response->assertStatus(200)
            ->assertJson(['unlocked' => true]);

        $this->assertDatabaseHas('share_unlocks', [
            'user_id' => $user->id,
            'target_profile_id' => $target->id,
            'platform' => 'copy_link',
        ]);
    }

    public function test_user_can_check_unlock_status()
    {
        $user = \App\Models\User::factory()->create();
        $target = \App\Models\User::factory()->create();

        Sanctum::actingAs($user);

        // Initially locked
        $this->getJson("/api/share-unlock/{$target->id}")
            ->assertJson(['unlocked' => false]);

        // Unlock
        \App\Models\ShareUnlock::create([
            'user_id' => $user->id,
            'target_profile_id' => $target->id,
            'platform' => 'whatsapp',
        ]);

        // Now unlocked
        $this->getJson("/api/share-unlock/{$target->id}")
            ->assertJson(['unlocked' => true]);
    }
}
