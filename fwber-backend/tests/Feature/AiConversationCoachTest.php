<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMatch;
use App\Services\AiWingmanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class AiConversationCoachTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_analyze_draft_message()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        $match = User::factory()->create();

        // Create match
        UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $match->id,
        ]);

        $this->actingAs($user);

        $draft = "Hey u up?";

        // Mock AiWingmanService
        $this->mock(AiWingmanService::class, function ($mock) use ($user, $match, $draft) {
            $mock->shouldReceive('analyzeDraftMessage')
                ->with(Mockery::type(User::class), Mockery::type(User::class), $draft, Mockery::type('array'))
                ->once()
                ->andReturn([
                    'score' => 40,
                    'tone' => 'Lazy',
                    'feedback' => 'This is a bit cliché and low effort.',
                    'suggestion' => 'Hey! How was your day?'
                ]);
        });

        $response = $this->postJson("/api/wingman/message-feedback/{$match->id}", [
            'draft' => $draft,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'score' => 40,
                'tone' => 'Lazy',
                'feedback' => 'This is a bit cliché and low effort.',
                'suggestion' => 'Hey! How was your day?'
            ]);
    }

    public function test_cannot_analyze_draft_if_not_matched()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson("/api/wingman/message-feedback/{$otherUser->id}", [
            'draft' => 'Hello',
        ]);

        $response->assertStatus(403);
    }

    public function test_requires_draft_field()
    {
        config(['features.ai_wingman' => true]);

        $user = User::factory()->create();
        $match = User::factory()->create();

        UserMatch::create([
            'user1_id' => $user->id,
            'user2_id' => $match->id,
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/wingman/message-feedback/{$match->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['draft']);
    }
}
