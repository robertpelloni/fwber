<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DecoyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_decoy_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/settings/decoy-profile', [
            'decoy_password' => 'secret_decoy_password',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Decoy profile created and linked successfully.',
                 ]);

        $user->refresh();
        $this->assertNotNull($user->decoy_user_id);
        $this->assertTrue(Hash::check('secret_decoy_password', $user->decoy_password));

        $decoyUser = User::find($user->decoy_user_id);
        $this->assertTrue((bool)$decoyUser->is_decoy);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $decoyUser->id,
        ]);
    }

    public function test_user_can_login_with_decoy_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('real_password'),
        ]);

        // Create decoy
        $this->actingAs($user)->postJson('/api/settings/decoy-profile', [
            'decoy_password' => 'secret_decoy_password',
        ]);
        
        $user->refresh();
        $decoyUserId = $user->decoy_user_id;

        // Attempt normal login
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'real_password',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($user->id, $response->json('user.id'));

        // Attempt decoy login
        $decoyResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email, // Same email!
            'password' => 'secret_decoy_password', // Decoy password
        ]);

        $decoyResponse->assertStatus(200);
        $this->assertEquals($decoyUserId, $decoyResponse->json('user.id'));
        $this->assertNotEquals($user->id, $decoyResponse->json('user.id'));
    }

    public function test_user_can_remove_decoy_profile()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/settings/decoy-profile', [
            'decoy_password' => 'secret_decoy_password',
        ]);

        $user->refresh();
        $decoyUserId = $user->decoy_user_id;
        $this->assertNotNull($decoyUserId);

        $response = $this->actingAs($user)->deleteJson('/api/settings/decoy-profile');
        $response->assertStatus(200);

        $user->refresh();
        $this->assertNull($user->decoy_user_id);
        $this->assertNull($user->decoy_password);
        
        // Decoy user should be deleted
        $this->assertNull(User::find($decoyUserId));
    }
}
