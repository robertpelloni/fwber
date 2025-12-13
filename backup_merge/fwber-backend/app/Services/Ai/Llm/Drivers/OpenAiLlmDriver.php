<?php

namespace App\Services\Ai\Llm\Drivers;

use App\DTOs\LlmResponse;
use App\Services\Ai\Llm\LlmProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiLlmDriver implements LlmProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4',
        private array $defaultConfig = []
    ) {}

    public function chat(array $messages, array $config = []): LlmResponse
    {
        $mergedConfig = array_merge($this->defaultConfig, $config);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $mergedConfig['model'] ?? $this->model,
                'messages' => $messages,
                'max_tokens' => $mergedConfig['max_tokens'] ?? 1000,
                'temperature' => $mergedConfig['temperature'] ?? 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                return new LlmResponse(
                    content: $content,
                    provider: 'openai',
                    metadata: [
                        'model' => $data['model'] ?? $this->model,
                        'usage' => $data['usage'] ?? [],
                        'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                    ]
                );
            }
            
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('OpenAI chat request failed', ['error' => $e->getMessage()]);
        }

        return new LlmResponse('', 'openai', ['error' => 'Request failed']);
    }

    public function moderate(string $content): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/moderations', [
                'input' => $content,
                'model' => 'text-moderation-latest',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $result = $data['results'][0] ?? [];
                
                return [
                    'flagged' => $result['flagged'] ?? false,
                    'categories' => $result['category_scores'] ?? [],
                    'score' => max($result['category_scores'] ?? [0]),
                ];
            }
            
            Log::error('OpenAI moderation error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('OpenAI moderation request failed', ['error' => $e->getMessage()]);
        }

        return ['flagged' => false, 'categories' => [], 'score' => 0.0, 'error' => 'Request failed'];
    }
}
