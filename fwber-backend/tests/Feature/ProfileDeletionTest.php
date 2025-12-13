<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_account()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Account deleted successfully']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        // Assuming cascade delete is set up in DB, profile should be gone too.
        // If it's soft delete, we might need to check trashed.
        // But standard $user->delete() with cascade DB constraints removes it.
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }

    public function test_unauthenticated_user_cannot_delete_account()
    {
        $response = $this->deleteJson('/api/profile');

        $response->assertStatus(401);
    }
}
