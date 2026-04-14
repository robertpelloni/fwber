<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserPublicKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityPubOutboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_endpoint_exposes_generated_public_key(): void
    {
        $user = $this->createFederatedUser('phoenix');

        $response = $this->getJson("/api/federation/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('publicKey.id', url("/api/federation/users/{$user->id}").'#main-key');

        $publicKeyPem = $response->json('publicKey.publicKeyPem');

        $this->assertIsString($publicKeyPem);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $publicKeyPem);
        $this->assertStringNotContainsString('MockPublicKeyForIteration0347', $publicKeyPem);
        $this->assertDatabaseHas('user_public_keys', [
            'user_id' => $user->id,
            'key_type' => 'ActivityPubRSA',
        ]);

        $keyRecord = UserPublicKey::where('user_id', $user->id)
            ->where('key_type', 'ActivityPubRSA')
            ->first();

        $this->assertNotNull($keyRecord?->private_key);
    }

    public function test_follow_endpoint_dispatches_signed_follow_to_remote_inbox(): void
    {
        $user = $this->createFederatedUser('sol');
        Sanctum::actingAs($user);

        $remoteActorUri = 'https://remote.test/users/ava';
        $remoteInboxUri = 'https://remote.test/inbox';

        Http::fake([
            $remoteActorUri => Http::response([
                'id' => $remoteActorUri,
                'type' => 'Person',
                'preferredUsername' => 'ava',
                'inbox' => $remoteInboxUri,
            ], 200),
            $remoteInboxUri => Http::response([], 202),
        ]);

        $response = $this->postJson('/api/federation/follow', [
            'actor_id' => $remoteActorUri,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('followings', [
            'user_id' => $user->id,
            'actor_uri' => $remoteActorUri,
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) use ($remoteInboxUri, $remoteActorUri, $user) {
            $signatureHeader = $request->header('Signature');
            $digestHeader = $request->header('Digest');
            $dateHeader = $request->header('Date');
            $payload = json_decode($request->body(), true);

            $signatureValue = is_array($signatureHeader) ? ($signatureHeader[0] ?? null) : $signatureHeader;
            $digestValue = is_array($digestHeader) ? ($digestHeader[0] ?? null) : $digestHeader;
            $dateValue = is_array($dateHeader) ? ($dateHeader[0] ?? null) : $dateHeader;

            return $request->url() === $remoteInboxUri
                && $request->method() === 'POST'
                && data_get($payload, 'type') === 'Follow'
                && data_get($payload, 'actor') === url("/api/federation/users/{$user->id}")
                && data_get($payload, 'object') === $remoteActorUri
                && is_string($signatureValue)
                && str_contains($signatureValue, 'keyId="'.url("/api/federation/users/{$user->id}").'#main-key"')
                && is_string($digestValue)
                && str_starts_with($digestValue, 'SHA-256=')
                && is_string($dateValue)
                && $dateValue !== '';
        });
    }

    protected function createFederatedUser(string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        return $user;
    }
}
