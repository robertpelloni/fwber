<?php

namespace App\Services\Ai\Llm\Drivers;

use App\DTOs\LlmResponse;
use App\Services\Ai\Llm\LlmProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicLlmDriver implements LlmProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'claude-3-5-sonnet-20241022',
        private array $defaultConfig = []
    ) {}

    public function chat(array $messages, array $config = []): LlmResponse
    {
        $mergedConfig = array_merge($this->defaultConfig, $config);
        
        try {
            // Extract system message if present
            $system = null;
            $filteredMessages = [];
            
            foreach ($messages as $msg) {
                if ($msg['role'] === 'system') {
                    $system = $msg['content'];
                } else {
                    $filteredMessages[] = $msg;
                }
            }

            $payload = [
                'model' => $mergedConfig['model'] ?? $this->model,
                'max_tokens' => $mergedConfig['max_tokens'] ?? 1000,
                'messages' => $filteredMessages,
                'temperature' => $mergedConfig['temperature'] ?? 0.7,
            ];

            if ($system) {
                $payload['system'] = $system;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? '';
                
                return new LlmResponse(
                    content: $content,
                    provider: 'claude',
                    metadata: [
                        'model' => $data['model'] ?? $this->model,
                        'usage' => $data['usage'] ?? [],
                        'stop_reason' => $data['stop_reason'] ?? null,
                    ]
                );
            }
            
            Log::error('Claude API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Claude chat request failed', ['error' => $e->getMessage()]);
        }

        return new LlmResponse('', 'claude', ['error' => 'Request failed']);
    }

    public function moderate(string $content): array
    {
        $prompt = "Analyze the following content for moderation. Respond with JSON only.\n\n" .
               "Content: \"$content\"\n\n" .
               "Check for: hate speech, harassment, violence, sexual content, spam, inappropriate language.\n" .
               "Respond with JSON: {\"flagged\": boolean, \"categories\": {\"hate\": 0.0-1.0, \"harassment\": 0.0-1.0, \"violence\": 0.0-1.0, \"sexual\": 0.0-1.0, \"spam\": 0.0-1.0}, \"reason\": \"explanation\"}";

        $response = $this->chat([['role' => 'user', 'content' => $prompt]], [
            'temperature' => 0.1,
            'max_tokens' => 1000,
        ]);

        if (!empty($response->metadata['error'])) {
             return ['flagged' => false, 'categories' => [], 'score' => 0.0, 'error' => $response->metadata['error']];
        }

        // Parse JSON from response
        preg_match('/\{.*\}/s', $response->content, $matches);
        $json = $matches[0] ?? '{}';
        $data = json_decode($json, true);

        return [
            'flagged' => $data['flagged'] ?? false,
            'categories' => $data['categories'] ?? [],
            'score' => isset($data['categories']) ? (count($data['categories']) > 0 ? max($data['categories']) : 0.0) : 0.0,
        ];
    }
}
