<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MercurePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalPulseRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'RealtimeTester',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'other',
            'location_latitude' => 40.0,
            'location_longitude' => -74.0,
        ]);
        $this->token = ApiToken::generateForUser($this->user, 'realtime-test');
    }

    #[Test]
    public function publishes_event_on_artifact_create(): void
    {
        $this->mock(MercurePublisher::class, function ($mock) {
            $mock->shouldReceive('publish')->once()->withArgs(function ($topic, $data, $private) {
                \PHPUnit\Framework\Assert::assertEquals('https://fwber.me/public/local-pulse', $topic);
                \PHPUnit\Framework\Assert::assertFalse($private);
                \PHPUnit\Framework\Assert::assertEquals('artifact_created', $data['type'] ?? null);
                \PHPUnit\Framework\Assert::assertArrayHasKey('artifact_id', $data);
                return true;
            });
        });

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'chat',
                'content' => 'Hello realtime',
                'lat' => 40.0001,
                'lng' => -74.0001,
            ])->assertCreated();
    }

    #[Test]
    public function publishes_event_on_artifact_flag(): void
    {
        $artifactId = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'board_post',
                'content' => 'Flag me',
                'lat' => 40.0,
                'lng' => -74.0,
            ])->json('artifact.id');

        $this->mock(MercurePublisher::class, function ($mock) use ($artifactId) {
            $mock->shouldReceive('publish')->once()->withArgs(function ($topic, $data, $private) use ($artifactId) {
                \PHPUnit\Framework\Assert::assertEquals('https://fwber.me/public/local-pulse', $topic);
                \PHPUnit\Framework\Assert::assertFalse($private);
                \PHPUnit\Framework\Assert::assertEquals('artifact_flagged', $data['type'] ?? null);
                \PHPUnit\Framework\Assert::assertEquals($artifactId, $data['artifact_id'] ?? null);
                return true;
            });
        });

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/proximity/artifacts/{$artifactId}/flag")
            ->assertOk();
    }

    #[Test]
    public function publishes_event_on_artifact_remove(): void
    {
        $artifactId = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'announce',
                'content' => 'Remove me',
                'lat' => 40.0,
                'lng' => -74.0,
            ])->json('artifact.id');

        $this->mock(MercurePublisher::class, function ($mock) use ($artifactId) {
            $mock->shouldReceive('publish')->once()->withArgs(function ($topic, $data, $private) use ($artifactId) {
                \PHPUnit\Framework\Assert::assertEquals('https://fwber.me/public/local-pulse', $topic);
                \PHPUnit\Framework\Assert::assertFalse($private);
                \PHPUnit\Framework\Assert::assertEquals('artifact_removed', $data['type'] ?? null);
                \PHPUnit\Framework\Assert::assertEquals($artifactId, $data['artifact_id'] ?? null);
                return true;
            });
        });

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/proximity/artifacts/{$artifactId}")
            ->assertOk();
    }
}
