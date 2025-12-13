<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPublicKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class E2EKeyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_public_key()
    {
        $user = User::factory()->create();
        $publicKey = 'test-public-key-123';

        $response = $this->actingAs($user)->postJson('/api/security/keys', [
            'public_key' => $publicKey,
            'key_type' => 'ECDH',
            'device_id' => 'device-1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Public key stored successfully.']);

        $this->assertDatabaseHas('user_public_keys', [
            'user_id' => $user->id,
            'key_type' => 'ECDH',
            'device_id' => 'device-1',
        ]);

        // Verify encryption
        $storedKey = UserPublicKey::where('user_id', $user->id)->first();
        $this->assertNotEquals($publicKey, $storedKey->public_key);
        $this->assertEquals($publicKey, Crypt::decryptString($storedKey->public_key));
    }

    public function test_user_can_retrieve_own_key()
    {
        $user = User::factory()->create();
        $publicKey = 'my-secret-key';
        
        UserPublicKey::create([
            'user_id' => $user->id,
            'public_key' => Crypt::encryptString($publicKey),
            'key_type' => 'ECDH',
        ]);

        $response = $this->actingAs($user)->getJson('/api/security/keys/me');

        $response->assertStatus(200)
            ->assertJson([
                'public_key' => $publicKey,
            ]);
    }

    public function test_user_can_retrieve_other_user_key()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $publicKey = 'user2-public-key';

        UserPublicKey::create([
            'user_id' => $user2->id,
            'public_key' => Crypt::encryptString($publicKey),
            'key_type' => 'ECDH',
        ]);

        $response = $this->actingAs($user1)->getJson("/api/security/keys/{$user2->id}");

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $user2->id,
                'public_key' => $publicKey,
            ]);
    }

    public function test_cannot_store_invalid_key_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/security/keys', [
            'public_key' => '', // Empty key
        ]);

        $response->assertStatus(422);
    }
}
