<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeFeedback;
use App\Models\Feedback;
use App\Models\User;
use App\Services\Ai\Llm\LlmManager;
use App\Services\Ai\Llm\LlmProviderInterface;
use App\DTOs\LlmResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class FeedbackAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_submission_dispatches_job()
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/feedback', [
            'category' => 'bug',
            'message' => 'Test message',
        ]);

        Queue::assertPushed(AnalyzeFeedback::class);
    }

    public function test_job_analyzes_feedback()
    {
        $user = User::factory()->create();

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'category' => 'general',
            'message' => 'The login button is broken on mobile.',
            'status' => 'new',
        ]);

        // Mock LLM
        $mockDriver = Mockery::mock(LlmProviderInterface::class);
        $mockDriver->shouldReceive('chat')
            ->once()
            ->andReturn(new LlmResponse(
                content: json_encode([
                    'category' => 'bug',
                    'sentiment' => 'negative',
                    'summary' => 'Login button issue on mobile.',
                    'tags' => ['mobile', 'auth']
                ]),
                provider: 'mock'
            ));

        $mockManager = Mockery::mock(LlmManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockDriver);

        $job = new AnalyzeFeedback($feedback->id);
        $job->handle($mockManager);

        $feedback->refresh();

        $this->assertEquals('bug', $feedback->category);
        $this->assertEquals('negative', $feedback->sentiment);
        $this->assertEquals('Login button issue on mobile.', $feedback->ai_analysis);
        $this->assertTrue((bool)$feedback->is_analyzed);
        $this->assertEquals(['ai_tags' => ['mobile', 'auth']], $feedback->metadata);
    }
}
