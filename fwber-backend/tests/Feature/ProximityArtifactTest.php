<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ProximityArtifact;
use App\Models\User;
use App\Models\UserProfile;
use Tests\Traits\RefreshDatabaseSilently;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProximityArtifactTest extends TestCase
{
    use RefreshDatabaseSilently;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Tester',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'other',
            'location_latitude' => 40.0,
            'location_longitude' => -74.0,
        ]);
        $this->token = ApiToken::generateForUser($this->user, 'test');
    }

    #[Test]
    public function create_and_fetch_artifact(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'chat',
                'content' => 'Hello world',
                'lat' => 40.0001,
                'lng' => -74.0001,
            ])->assertCreated();

        $feed = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/proximity/feed?lat=40&lng=-74&radius=1000')
            ->assertOk()
            ->json('artifacts');

        $this->assertCount(1, $feed);
        $this->assertEquals('chat', $feed[0]['type']);
    }

    #[Test]
    public function sanitizer_blocks_links_and_contact(): void
    {
        $badPayloads = [
            'Check this http://example.com',
            'Visit www.example.com now',
            'Call me 555-123-4567',
            'Email: test@example.com',
        ];
        foreach ($badPayloads as $payload) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/proximity/artifacts', [
                    'type' => 'chat',
                    'content' => $payload,
                    'lat' => 40.0,
                    'lng' => -74.0,
                ])->assertStatus(422);
        }
    }

    #[Test]
    public function daily_cap_enforced(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/proximity/artifacts', [
                    'type' => 'chat',
                    'content' => 'Msg '.$i,
                    'lat' => 40.0,
                    'lng' => -74.0,
                ])->assertCreated();
        }
        // 31st should fail
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'chat',
                'content' => 'Overflow',
                'lat' => 40.0,
                'lng' => -74.0,
            ])->assertStatus(422);
    }

    #[Test]
    public function flag_escalates_status(): void
    {
        $artifactId = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'board_post',
                'content' => 'Board content',
                'lat' => 40.0,
                'lng' => -74.0,
            ])->json('artifact.id');

        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson("/api/proximity/artifacts/{$artifactId}/flag")
                ->assertOk();
        }
        $this->assertDatabaseHas('proximity_artifacts', [
            'id' => $artifactId,
            'moderation_status' => 'flagged'
        ]);
    }

    #[Test]
    public function removal_marks_status_removed(): void
    {
        $artifactId = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/proximity/artifacts', [
                'type' => 'announce',
                'content' => 'Announcement',
                'lat' => 40.0,
                'lng' => -74.0,
            ])->json('artifact.id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/proximity/artifacts/{$artifactId}")
            ->assertOk();

        $this->assertDatabaseHas('proximity_artifacts', [
            'id' => $artifactId,
            'moderation_status' => 'removed'
        ]);
    }
}
