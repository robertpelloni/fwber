<?php

namespace Tests\Feature;

use App\Models\DateFeedback;
use App\Models\User;
use App\Models\UserMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_date_feedback(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user1)->postJson("/api/settings/matches/{$match->id}/feedback", [
            'rating' => 4,
            'feedback_text' => 'Great time at the coffee shop.',
            'safety_concerns' => false,
        ]);

        // Wait, the route is actually /api/matches/{id}/feedback
        $response = $this->actingAs($user1)->postJson("/api/matches/{$match->id}/feedback", [
            'rating' => 4,
            'feedback_text' => 'Great time at the coffee shop.',
            'safety_concerns' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('feedback.rating', 4);

        $this->assertDatabaseHas('date_feedback', [
            'match_id' => $match->id,
            'reporting_user_id' => $user1->id,
            'subject_user_id' => $user2->id,
            'rating' => 4,
        ]);
    }

    public function test_user_cannot_submit_duplicate_feedback(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        DateFeedback::create([
            'match_id' => $match->id,
            'reporting_user_id' => $user1->id,
            'subject_user_id' => $user2->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user1)->postJson("/api/matches/{$match->id}/feedback", [
            'rating' => 3,
            'feedback_text' => 'Changed my mind.',
            'safety_concerns' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('match_id');
    }

    public function test_user_can_check_if_feedback_submitted(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $match = UserMatch::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Initially shouldn't exist
        $response = $this->actingAs($user1)->getJson("/api/matches/{$match->id}/feedback");
        $response->assertStatus(404);

        DateFeedback::create([
            'match_id' => $match->id,
            'reporting_user_id' => $user1->id,
            'subject_user_id' => $user2->id,
            'rating' => 5,
        ]);

        // Now should exist
        $response = $this->actingAs($user1)->getJson("/api/matches/{$match->id}/feedback");
        $response->assertStatus(200);
        $response->assertJsonPath('submitted', true);
        $response->assertJsonPath('feedback.rating', 5);
    }
}
