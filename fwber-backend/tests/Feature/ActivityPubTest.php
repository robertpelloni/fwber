<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;

class ActivityPubTest extends TestCase
{
    use RefreshDatabase;

    public function test_webfinger_resolves_federated_user()
    {
        $user = User::factory()->create(['name' => 'alice']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true
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
            'is_federated' => false
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
            'bio' => 'Charlie bio'
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
            'is_federated' => true
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://remote.test/activity/1',
            'type' => 'Follow',
            'actor' => 'https://remote.test/users/remote-user',
            'object' => url("/api/federation/users/{$user->id}")
        ];

        $response = $this->postJson("/api/federation/users/{$user->id}/inbox", $payload);

        $response->assertStatus(202)
            ->assertJson(['status' => 'follow_processed']);

        $this->assertDatabaseHas('followers', [
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user'
        ]);
    }

    public function test_inbox_handles_unfollow()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true
        ]);

        \App\Models\Follower::create([
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user',
            'status' => 'accepted'
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Undo',
            'actor' => 'https://remote.test/users/remote-user',
            'object' => [
                'type' => 'Follow',
                'actor' => 'https://remote.test/users/remote-user',
                'object' => url("/api/federation/users/{$user->id}")
            ]
        ];

        $response = $this->postJson("/api/federation/users/{$user->id}/inbox", $payload);

        $response->assertStatus(202)
            ->assertJson(['status' => 'unfollow_processed']);

        $this->assertDatabaseMissing('followers', [
            'user_id' => $user->id,
            'actor_uri' => 'https://remote.test/users/remote-user'
        ]);
    }

    public function test_outbox_returns_ordered_collection()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_federated' => true
        ]);

        $response = $this->getJson("/api/federation/users/{$user->id}/outbox");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/activity+json')
            ->assertJsonPath('type', 'OrderedCollection');
    }
}