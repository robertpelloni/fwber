<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ActivityPubSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_rejects_request_without_signature_header(): void
    {
        $user = $this->createFederatedUser();

        $response = $this->postJson("/api/federation/users/{$user->id}/inbox", [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => 'https://remote.test/users/unsigned',
            'object' => url("/api/federation/users/{$user->id}"),
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Missing Signature header']);
    }

    public function test_inbox_rejects_invalid_signature(): void
    {
        $user = $this->createFederatedUser();
        [$privateKey, $publicKey] = $this->generateActivityPubKeyPair();
        $actorUri = 'https://remote.test/users/ava';

        Http::fake([
            $actorUri => Http::response($this->remoteActorDocument($actorUri, $publicKey), 200),
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => $actorUri,
            'object' => url("/api/federation/users/{$user->id}"),
        ];

        $response = $this->postSignedInboxActivity($user->id, $payload, $actorUri, $privateKey, [
            'signature' => base64_encode('not-a-real-signature'),
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Signature verification failed']);
    }

    public function test_inbox_rejects_stale_date_header(): void
    {
        $user = $this->createFederatedUser();
        [$privateKey] = $this->generateActivityPubKeyPair();

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => 'https://remote.test/users/ava',
            'object' => url("/api/federation/users/{$user->id}"),
        ];

        $response = $this->postSignedInboxActivity($user->id, $payload, 'https://remote.test/users/ava', $privateKey, [
            'date' => now()->subMinutes(15)->toRfc7231String(),
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Stale or missing Date header']);
    }

    public function test_inbox_rejects_when_signature_key_does_not_match_payload_actor(): void
    {
        $user = $this->createFederatedUser();
        [$privateKey, $publicKey] = $this->generateActivityPubKeyPair();

        Http::fake([
            'https://remote.test/users/alice' => Http::response(
                $this->remoteActorDocument('https://remote.test/users/alice', $publicKey),
                200
            ),
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => 'https://remote.test/users/bob',
            'object' => url("/api/federation/users/{$user->id}"),
        ];

        $response = $this->postSignedInboxActivity($user->id, $payload, 'https://remote.test/users/alice', $privateKey);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Signature actor does not match payload actor']);
    }

    protected function createFederatedUser(): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        return $user;
    }

    protected function postSignedInboxActivity(int $userId, array $payload, string $actorUri, string $privateKey, array $overrides = []): TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $date = $overrides['date'] ?? now()->toRfc7231String();
        $host = $overrides['host'] ?? $this->activityPubTestHost();
        $contentType = $overrides['content_type'] ?? 'application/json';
        $headersList = $overrides['headers'] ?? '(request-target) host date digest content-type';
        $digest = $overrides['digest'] ?? 'SHA-256='.base64_encode(hash('sha256', $body, true));
        $signature = $overrides['signature'] ?? $this->signActivityPubRequest(
            $privateKey,
            'post',
            "/api/federation/users/{$userId}/inbox",
            $host,
            $date,
            $digest,
            $contentType,
            $headersList
        );

        $signatureHeader = sprintf(
            'keyId="%s",algorithm="rsa-sha256",headers="%s",signature="%s"',
            $actorUri.'#main-key',
            $headersList,
            $signature
        );

        return $this->call(
            'POST',
            "/api/federation/users/{$userId}/inbox",
            [],
            [],
            [],
            [
                'HTTP_HOST' => $host,
                'HTTP_DATE' => $date,
                'HTTP_DIGEST' => $digest,
                'HTTP_SIGNATURE' => $signatureHeader,
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT' => 'application/json',
            ],
            $body
        );
    }

    protected function signActivityPubRequest(
        string $privateKey,
        string $method,
        string $path,
        string $host,
        string $date,
        string $digest,
        string $contentType,
        string $headersList
    ): string {
        $headerValues = [
            '(request-target)' => strtolower($method).' '.$path,
            'host' => $host,
            'date' => $date,
            'digest' => $digest,
            'content-type' => $contentType,
        ];

        $signingLines = collect(explode(' ', $headersList))
            ->filter()
            ->map(fn (string $header): string => strtolower($header).': '.$headerValues[strtolower($header)])
            ->implode("\n");

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false || ! openssl_sign($signingLines, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign ActivityPub test request.');
        }

        return base64_encode($signature);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function generateActivityPubKeyPair(): array
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyPair, $privateKey);
        $details = openssl_pkey_get_details($keyPair);

        return [$privateKey, $details['key']];
    }

    protected function remoteActorDocument(string $actorUri, string $publicKey): array
    {
        return [
            'id' => $actorUri,
            'type' => 'Person',
            'preferredUsername' => basename(parse_url($actorUri, PHP_URL_PATH)),
            'inbox' => $actorUri.'/inbox',
            'outbox' => $actorUri.'/outbox',
            'publicKey' => [
                'id' => $actorUri.'#main-key',
                'owner' => $actorUri,
                'publicKeyPem' => $publicKey,
            ],
        ];
    }

    protected function activityPubTestHost(): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $port = parse_url(config('app.url'), PHP_URL_PORT);

        return $port ? "{$host}:{$port}" : $host;
    }
}
