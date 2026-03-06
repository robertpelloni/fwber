<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\ProximityArtifact;
use App\Models\ProximityArtifactComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProximityArtifactCommentTest extends TestCase
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

    public function test_user_can_comment_on_artifact(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/comments",
            ['content' => 'This is a test comment']
        );

        $response->assertStatus(201)
            ->assertJsonPath('comment.content', 'This is a test comment');

        $this->assertDatabaseHas('proximity_artifact_comments', [
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
            'parent_id' => null
        ]);
    }

    public function test_user_can_reply_to_comment(): void
    {
        // First, create a parent comment
        $parentComment = ProximityArtifactComment::create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => User::factory()->create()->id,
            'content' => 'Parent comment'
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/comments",
            [
                'content' => 'This is a reply',
                'parent_id' => $parentComment->id
            ]
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('proximity_artifact_comments', [
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id
        ]);
    }

    public function test_user_can_view_comments(): void
    {
        // Create 2 top level comments
        ProximityArtifactComment::factory()->create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'Comment 1',
            'created_at' => now()->subMinutes(1)
        ]);
        
        $comment2 = ProximityArtifactComment::factory()->create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'Comment 2',
            'created_at' => now()
        ]);

        // Create 1 reply
        ProximityArtifactComment::create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'Reply to comment 2',
            'parent_id' => $comment2->id
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/proximity/artifacts/{$this->artifact->id}/comments"
        );

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Top level comments
            ->assertJsonPath('data.0.content', 'Comment 2') // Ordered by newest first (desc by default)
            ->assertJsonCount(1, 'data.0.replies') // The replies relationship
            ->assertJsonPath('data.0.replies.0.content', 'Reply to comment 2');
    }

    public function test_user_cannot_comment_invalid_content(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/comments",
            ['content' => '']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
            
        $responseTooLong = $this->actingAs($this->user)->postJson(
            "/api/proximity/artifacts/{$this->artifact->id}/comments",
            ['content' => str_repeat('a', 1001)]
        );

        $responseTooLong->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $comment = ProximityArtifactComment::create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $this->user->id,
            'content' => 'My comment'
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/proximity/artifacts/comments/{$comment->id}"
        );

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comment deleted']);

        $this->assertSoftDeleted('proximity_artifact_comments', [
            'id' => $comment->id
        ]);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = ProximityArtifactComment::create([
            'proximity_artifact_id' => $this->artifact->id,
            'user_id' => $otherUser->id,
            'content' => 'Other users comment'
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/proximity/artifacts/comments/{$comment->id}"
        );

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);

        $this->assertDatabaseHas('proximity_artifact_comments', [
            'id' => $comment->id
        ]);
    }
}
