<?php

namespace Tests\Feature;

use App\Models\FederatedPost;
use App\Models\Following;
use App\Models\ProximityArtifact;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityPubTest extends TestCase
{
    use RefreshDatabase;

    public function test_webfinger_resolves_federated_user()
    {
        $user = User::factory()->create(['name' => 'alice']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        $resource = "acct:alice@{$domain}";

        $response = $this->getJson("/.well-known/webfinger?resource={$resource}");

        $response->assertStatus(200)
            ->assertJsonPath('subject', $resource)
            ->assertJsonPath('links.0.rel', 'self')
            ->assertJsonPath('links.0.href', url("/api/federation/users/{$user->id}"));
    }

    public function test_webfinger_returns_404_for_non_federated_user()
    {
        $user = User::factory()->create(['name' => 'bob']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => false,
        ]);

        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        $resource = "acct:bob@{$domain}";

        $response = $this->getJson("/.well-known/webfinger?resource={$resource}");

        $response->assertStatus(404);
    }

    public function test_actor_endpoint_returns_json_ld()
    {
        $user = User::factory()->create(['name' => 'charlie']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
            'bio' => 'Charlie bio',
        ]);

        $response = $this->getJson("/api/federation/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('type', 'Person')
            ->assertJsonPath('preferredUsername', 'charlie')
            ->assertJsonPath('summary', 'Charlie bio');
    }

    public function test_inbox_accepts_activity()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://remote.test/activity/1',
            'type' => 'Follow',
            'actor' => 'https://remote.test/users/remote-user',
            'object' => url("/api/federation/users/{$user->id}"),
        ];

        [$privateKey, $publicKey] = $this->generateActivityPubKeyPair();
        Http::fake([
            'https://remote.test/users/remote-user' => Http::response(
                $this->remoteActorDocument('https://remote.test/users/remote-user', $publicKey),
                200
            ),
        ]);

        $response = $this->postSignedInboxActivity($user->id, $payload, 'https://remote.test/users/remote-user', $privateKey);

        $response->assertStatus(202)
            ->assertJson(['status' => 'follow_processed']);

        $this->assertDatabaseHas('followers', [
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user',
        ]);
    }

    public function test_inbox_handles_unfollow()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        \App\Models\Follower::create([
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user',
            'status' => 'accepted',
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Undo',
            'actor' => 'https://remote.test/users/remote-user',
            'object' => [
                'type' => 'Follow',
                'actor' => 'https://remote.test/users/remote-user',
                'object' => url("/api/federation/users/{$user->id}"),
            ],
        ];

        [$privateKey, $publicKey] = $this->generateActivityPubKeyPair();
        Http::fake([
            'https://remote.test/users/remote-user' => Http::response(
                $this->remoteActorDocument('https://remote.test/users/remote-user', $publicKey),
                200
            ),
        ]);

        $response = $this->postSignedInboxActivity($user->id, $payload, 'https://remote.test/users/remote-user', $privateKey);

        $response->assertStatus(202)
            ->assertJson(['status' => 'unfollow_processed']);

        $this->assertDatabaseMissing('followers', [
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user',
        ]);
    }

    public function test_inbox_handles_follow_accept_for_pending_following()
    {
        $user = User::factory()->create(['name' => 'local-user']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        Following::create([
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/ava',
            'username' => 'ava',
            'domain' => 'remote.test',
            'status' => 'pending',
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Accept',
            'actor' => 'https://remote.test/users/ava',
            'object' => [
                'type' => 'Follow',
                'actor' => url("/api/federation/users/{$user->id}"),
                'object' => 'https://remote.test/users/ava',
            ],
        ];

        [$privateKey, $publicKey] = $this->generateActivityPubKeyPair();
        Http::fake([
            'https://remote.test/users/ava' => Http::response(
                $this->remoteActorDocument('https://remote.test/users/ava', $publicKey),
                200
            ),
        ]);

        $response = $this->postSignedInboxActivity($user->id, $payload, 'https://remote.test/users/ava', $privateKey);

        $response->assertStatus(202)
            ->assertJson(['status' => 'accept_processed']);

        $this->assertDatabaseHas('followings', [
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/ava',
            'status' => 'accepted',
        ]);
    }

    public function test_outbox_returns_ordered_collection()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        $response = $this->getJson("/api/federation/users/{$user->id}/outbox");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/activity+json')
            ->assertJsonPath('type', 'OrderedCollection');
    }

    public function test_outbox_page_returns_active_board_posts_as_create_activities()
    {
        $user = User::factory()->create(['name' => 'delta']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'type' => 'board_post',
            'content' => 'Hello from fwber federation',
            'expires_at' => now()->addHour(),
            'created_at' => now()->subMinutes(5),
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'type' => 'announce',
            'content' => 'This should not appear in the outbox',
            'expires_at' => now()->addHour(),
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'type' => 'board_post',
            'content' => 'This expired post should stay out of the public outbox',
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->getJson("/api/federation/users/{$user->id}/outbox?page=true&limit=10");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/activity+json')
            ->assertJsonPath('type', 'OrderedCollectionPage')
            ->assertJsonPath('partOf', url("/api/federation/users/{$user->id}/outbox"))
            ->assertJsonPath('totalItems', 1)
            ->assertJsonCount(1, 'orderedItems')
            ->assertJsonPath('orderedItems.0.type', 'Create')
            ->assertJsonPath('orderedItems.0.actor', url("/api/federation/users/{$user->id}"))
            ->assertJsonPath('orderedItems.0.object.type', 'Note')
            ->assertJsonPath('orderedItems.0.object.content', 'Hello from fwber federation');
    }

    public function test_actor_detail_returns_remote_actor_with_cached_context()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true,
        ]);

        Sanctum::actingAs($user);

        Following::create([
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/ava',
            'username' => 'ava',
            'domain' => 'remote.test',
            'status' => 'pending',
        ]);

        FederatedPost::create([
            'guid' => 'remote-note-1',
            'actor_uri' => 'https://remote.test/users/ava',
            'actor_username' => 'ava',
            'actor_domain' => 'remote.test',
            'content' => '<p>Hello from the fediverse</p>',
            'url' => 'https://remote.test/@ava/1',
            'metadata' => [
                'name' => 'Ava Remote',
                'preferredUsername' => 'ava',
                'summary' => 'Cached summary',
            ],
            'published_at' => now()->subMinute(),
        ]);

        Http::fake([
            'https://remote.test/users/ava' => Http::response([
                'id' => 'https://remote.test/users/ava',
                'type' => 'Person',
                'preferredUsername' => 'ava',
                'name' => 'Ava Remote',
                'summary' => '<p>Remote summary</p>',
                'url' => 'https://remote.test/@ava',
                'inbox' => 'https://remote.test/users/ava/inbox',
                'outbox' => 'https://remote.test/users/ava/outbox',
            ], 200),
        ]);

        $response = $this->getJson('/api/federation/actors/detail?uri='.urlencode('https://remote.test/users/ava'));

        $response->assertStatus(200)
            ->assertJsonPath('actor.id', 'https://remote.test/users/ava')
            ->assertJsonPath('actor.preferredUsername', 'ava')
            ->assertJsonPath('actor.name', 'Ava Remote')
            ->assertJsonPath('actor.summary', 'Remote summary')
            ->assertJsonPath('actor.cachedPostsCount', 1)
            ->assertJsonPath('actor.followingStatus', 'pending')
            ->assertJsonPath('actor.url', 'https://remote.test/@ava');
    }

    public function test_posts_endpoint_supports_actor_filtering()
    {
        FederatedPost::create([
            'guid' => 'remote-note-1',
            'actor_uri' => 'https://remote.test/users/ava',
            'actor_username' => 'ava',
            'actor_domain' => 'remote.test',
            'content' => '<p>Hello from Ava</p>',
            'url' => 'https://remote.test/@ava/1',
            'published_at' => now()->subMinute(),
        ]);

        FederatedPost::create([
            'guid' => 'remote-note-2',
            'actor_uri' => 'https://elsewhere.test/users/max',
            'actor_username' => 'max',
            'actor_domain' => 'elsewhere.test',
            'content' => '<p>Hello from Max</p>',
            'url' => 'https://elsewhere.test/@max/1',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/federation/posts?actor_uri='.urlencode('https://remote.test/users/ava').'&limit=10');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'posts')
            ->assertJsonPath('posts.0.actor_uri', 'https://remote.test/users/ava');
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
