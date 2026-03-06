<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProximityArtifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProximityArtifactVoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProximityArtifact $artifact;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->user->id]);

        $this->artifact = ProximityArtifact::factory()->create([
            'user_id' => User::factory()->create()->id,
            'type' => 'board_post',
            'content' => 'Test post'
        ]);
    }

    public function test_user_can_upvote_artifact(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/vote",
            ['value' => 1]
        );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Vote recorded'
            ]);

        $this->assertDatabaseHas('proximity_artifact_votes', [
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'value' => 1
        ]);
    }

    public function test_user_can_downvote_artifact(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/vote",
            ['value' => -1]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('proximity_artifact_votes', [
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'value' => -1
        ]);
    }

    public function test_user_can_remove_vote(): void
    {
        // Add initial vote
        $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/vote",
            ['value' => 1]
        );

        // Remove vote
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/vote",
            ['value' => 0]
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('proximity_artifact_votes', [
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_cannot_vote_invalid_value(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/vote",
            ['value' => 2]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }
}
