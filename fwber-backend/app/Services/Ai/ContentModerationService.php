<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ContentModerationService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? '';
    }

    /**
     * Moderate the given text content.
     *
     * @param string $text
     * @return array{flagged: bool, categories: array}
     */
    public function moderate(string $text): array
    {
        if (empty($text)) {
            return ['flagged' => false, 'categories' => []];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/moderations", [
                    'input' => $text,
                ]);

            if ($response->successful()) {
                $result = $response->json('results.0');
                
                if (!$result) {
                    return ['flagged' => false, 'categories' => []];
                }

                $flagged = $result['flagged'] ?? false;
                $categories = [];

                if ($flagged && isset($result['categories'])) {
                    // Filter categories that are true
                    $categories = array_keys(array_filter($result['categories'], function ($value) {
                        return $value === true;
                    }));
                }

                return [
                    'flagged' => $flagged,
                    'categories' => $categories,
                ];
            }

            Log::error('OpenAI Moderation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['flagged' => false, 'categories' => []];
        } catch (Exception $e) {
            Log::error('Content moderation exception', ['error' => $e->getMessage()]);
            return ['flagged' => false, 'categories' => []];
        }
    }
}
