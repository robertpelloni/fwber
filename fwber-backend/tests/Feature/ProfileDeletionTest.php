<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_account_with_valid_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson('/api/profile', [
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Account deleted successfully']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }

    public function test_user_cannot_delete_account_with_wrong_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_account_without_password()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/profile');

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_unauthenticated_user_cannot_delete_account()
    {
        $response = $this->deleteJson('/api/profile');

        $response->assertStatus(401);
    }
}
