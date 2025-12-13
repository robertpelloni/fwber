<?php

namespace App\Services\Ai\Llm\Drivers;

use App\DTOs\LlmResponse;
use App\Services\Ai\Llm\LlmProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiLlmDriver implements LlmProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-pro',
        private array $defaultConfig = []
    ) {}

    public function chat(array $messages, array $config = []): LlmResponse
    {
        $mergedConfig = array_merge($this->defaultConfig, $config);
        $model = $mergedConfig['model'] ?? $this->model;
        
        try {
            // Convert standard messages format to Gemini format
            $contents = $this->convertMessages($messages);
            
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";
            
            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $mergedConfig['temperature'] ?? 0.7,
                    'maxOutputTokens' => $mergedConfig['max_tokens'] ?? 1000,
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return new LlmResponse(
                    content: $content,
                    provider: 'gemini',
                    metadata: [
                        'model' => $model,
                        'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
                    ]
                );
            }
            
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gemini chat request failed', ['error' => $e->getMessage()]);
        }

        return new LlmResponse('', 'gemini', ['error' => 'Request failed']);
    }

    private function convertMessages(array $messages): array
    {
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            $role = $msg['role'];
            $content = $msg['content'];

            if ($role === 'system') {
                // Gemini Pro 1.0 doesn't support system instructions directly in contents well, 
                // but newer versions do. For compatibility, we'll prepend to first user message 
                // or handle if we upgrade to 1.5.
                // For now, let's just treat it as a user message with a prefix or merge it.
                // A common pattern is to prepend it to the first user message.
                $systemInstruction = $content;
                continue;
            }

            $geminiRole = ($role === 'assistant') ? 'model' : 'user';
            
            if ($systemInstruction && $geminiRole === 'user') {
                $content = "System Instruction: " . $systemInstruction . "\n\n" . $content;
                $systemInstruction = null; // Consumed
            }

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [
                    ['text' => $content]
                ]
            ];
        }
        
        // If system instruction is still pending (e.g. no user message), add it as user
        if ($systemInstruction) {
             $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "System Instruction: " . $systemInstruction]
                ]
            ];
        }

        return $contents;
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
