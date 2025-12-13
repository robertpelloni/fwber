<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_feedback()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/feedback', [
            'category' => 'bug',
            'message' => 'This is a test bug report',
            'page_url' => '/dashboard',
            'metadata' => ['browser' => 'Chrome'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'category' => 'bug',
            'message' => 'This is a test bug report',
        ]);
    }

    public function test_feedback_validation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/feedback', [
            'category' => 'invalid_category',
            'message' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category', 'message']);
    }
}
