<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MercurePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MercureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_proximity_artifact_creation_publishes_mercure_event()
    {
        $user = User::factory()->create();
        $user->profile()->create();

        // Mock the MercurePublisher
        $mercureMock = Mockery::mock(MercurePublisher::class);
        $mercureMock->shouldReceive('publish')
            ->once()
            ->withArgs(function ($topic, $data, $private) use ($user) {
                return $topic === 'https://fwber.me/public/local-pulse'
                    && $data['type'] === 'artifact_created'
                    && $data['user_id'] === $user->id
                    && $private === false;
            });

        // Swap the instance in the container
        $this->app->instance(MercurePublisher::class, $mercureMock);

        $response = $this->actingAs($user)
            ->postJson('/api/proximity/artifacts', [
                'type' => 'chat',
                'content' => 'Hello world',
                'lat' => 40.7128,
                'lng' => -74.0060,
                'radius' => 1000,
            ]);

        $response->assertStatus(201);
    }

    public function test_flagging_artifact_publishes_mercure_event()
    {
        $user = User::factory()->create();
        $user->profile()->create();
        
        $artifact = \App\Models\ProximityArtifact::factory()->create();

        // Mock the MercurePublisher
        $mercureMock = Mockery::mock(MercurePublisher::class);
        $mercureMock->shouldReceive('publish')
            ->once()
            ->withArgs(function ($topic, $data, $private) use ($user, $artifact) {
                return $topic === 'https://fwber.me/public/local-pulse'
                    && $data['type'] === 'artifact_flagged'
                    && $data['artifact_id'] === $artifact->id
                    && $data['flagged_by'] === $user->id
                    && $private === false;
            });

        $this->app->instance(MercurePublisher::class, $mercureMock);

        $response = $this->actingAs($user)
            ->postJson("/api/proximity/artifacts/{$artifact->id}/flag");

        $response->assertStatus(200);
    }
}
