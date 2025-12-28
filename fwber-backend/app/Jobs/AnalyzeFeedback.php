<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Services\Ai\Llm\LlmManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeFeedback implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $feedbackId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $feedbackId)
    {
        $this->feedbackId = $feedbackId;
    }

    /**
     * Execute the job.
     */
    public function handle(LlmManager $llmManager): void
    {
        $feedback = Feedback::find($this->feedbackId);

        if (!$feedback) {
            return;
        }

        try {
            $prompt = $this->buildPrompt($feedback);
            
            $response = $llmManager->driver()->chat([
                ['role' => 'system', 'content' => 'You are a product manager assistant. Analyze the user feedback and categorize it.'],
                ['role' => 'user', 'content' => $prompt]
            ], ['temperature' => 0.3]);

            $analysis = $this->parseResponse($response->content);

            $feedback->update([
                'category' => $analysis['category'] ?? $feedback->category,
                'sentiment' => $analysis['sentiment'] ?? 'neutral',
                'ai_analysis' => $analysis['summary'] ?? null,
                'is_analyzed' => true,
            ]);

        } catch (\Exception $e) {
            Log::error("AnalyzeFeedback Job Failed: " . $e->getMessage());
        }
    }

    protected function buildPrompt(Feedback $feedback): string
    {
        return <<<EOT
Analyze the following user feedback:

Message: "{$feedback->message}"
Page URL: "{$feedback->page_url}"
Current Category: "{$feedback->category}"

Output a JSON object with:
- category: (string) One of: "bug", "feature_request", "ux_issue", "praise", "safety", "other".
- sentiment: (string) One of: "positive", "negative", "neutral".
- summary: (string) A 1-sentence summary of the core issue or request.
EOT;
    }

    protected function parseResponse(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [
            'category' => 'general',
            'sentiment' => 'neutral',
            'summary' => 'Could not parse analysis.'
        ];
    }
}
