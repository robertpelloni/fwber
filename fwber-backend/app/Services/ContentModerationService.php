<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContentModerationService
{
    private string $openaiApiKey;
    private string $geminiApiKey;
    private array $moderationConfig;

    public function __construct()
    {
        // Default to empty strings if missing to avoid TypeError in tests
        $this->openaiApiKey = (string) (config('services.openai.api_key') ?? '');
        $this->geminiApiKey = (string) (config('services.gemini.api_key') ?? '');
        $this->moderationConfig = config('moderation', [
            'enabled' => true,
            'providers' => ['openai', 'gemini'],
            'thresholds' => [
                'hate' => 0.8,
                'harassment' => 0.8,
                'violence' => 0.8,
                'sexual' => 0.8,
                'spam' => 0.7,
            ],
            'cache_ttl' => 3600, // 1 hour
        ]);
    }

    /**
     * Moderate content using multiple AI providers
     */
    public function moderateContent(string $content, array $context = []): array
    {
        $cacheKey = 'moderation_' . md5($content);
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $results = [];
        
        // OpenAI moderation (skip if API key missing)
        if (in_array('openai', $this->moderationConfig['providers']) && $this->openaiApiKey !== '') {
            $results['openai'] = $this->moderateWithOpenAI($content);
        }
        
        // Gemini moderation (skip if API key missing)
        if (in_array('gemini', $this->moderationConfig['providers']) && $this->geminiApiKey !== '') {
            $results['gemini'] = $this->moderateWithGemini($content, $context);
        }
        
        // If no providers available, return safe-by-default heuristic
        if (empty($results)) {
            $finalResult = [
                'flagged' => false,
                'categories' => [],
                'reasons' => [],
                'confidence' => 0.0,
                'providers' => [],
                'action' => 'approve',
                'content_length' => strlen($content),
                'timestamp' => now()->toISOString(),
            ];
        } else {
            // Combine results
            $finalResult = $this->combineModerationResults($results, $content);
        }
        
        // Cache result
        Cache::put($cacheKey, $finalResult, $this->moderationConfig['cache_ttl']);
        
        return $finalResult;
    }

    /**
     * Moderate content using OpenAI API
     */
    private function moderateWithOpenAI(string $content): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/moderations', [
                'input' => $content,
                'model' => 'text-moderation-latest'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'][0] ?? [];
                
                return [
                    'flagged' => $results['flagged'] ?? false,
                    'categories' => $results['category_scores'] ?? [],
                    'confidence' => $this->calculateConfidence($results['category_scores'] ?? []),
                    'provider' => 'openai'
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI moderation failed', ['error' => $e->getMessage()]);
        }

        return ['flagged' => false, 'error' => 'OpenAI moderation unavailable'];
    }

    /**
     * Moderate content using Gemini API
     */
    private function moderateWithGemini(string $content, array $context = []): array
    {
        try {
            $prompt = $this->buildGeminiPrompt($content, $context);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 1000,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return $this->parseGeminiResponse($text);
            }
        } catch (\Exception $e) {
            Log::error('Gemini moderation failed', ['error' => $e->getMessage()]);
        }

        return ['flagged' => false, 'error' => 'Gemini moderation unavailable'];
    }

    /**
     * Build prompt for Gemini moderation
     */
    private function buildGeminiPrompt(string $content, array $context = []): string
    {
        $contextStr = !empty($context) ? "Context: " . json_encode($context) . "\n" : "";
        
        return "Analyze the following content for moderation. Respond with JSON only.\n\n" .
               $contextStr .
               "Content: \"$content\"\n\n" .
               "Check for: hate speech, harassment, violence, sexual content, spam, inappropriate language.\n" .
               "Respond with JSON: {\"flagged\": boolean, \"categories\": {\"hate\": 0.0-1.0, \"harassment\": 0.0-1.0, \"violence\": 0.0-1.0, \"sexual\": 0.0-1.0, \"spam\": 0.0-1.0}, \"reason\": \"explanation\"}";
    }

    /**
     * Parse Gemini response
     */
    private function parseGeminiResponse(string $response): array
    {
        try {
            // Extract JSON from response
            preg_match('/\{.*\}/', $response, $matches);
            $json = $matches[0] ?? '{}';
            $data = json_decode($json, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'flagged' => $data['flagged'] ?? false,
                    'categories' => $data['categories'] ?? [],
                    'reason' => $data['reason'] ?? '',
                    'confidence' => $this->calculateConfidence($data['categories'] ?? []),
                    'provider' => 'gemini'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to parse Gemini response', ['response' => $response, 'error' => $e->getMessage()]);
        }

        return ['flagged' => false, 'error' => 'Failed to parse Gemini response'];
    }

    /**
     * Combine results from multiple providers
     */
    private function combineModerationResults(array $results, string $content): array
    {
        $flagged = false;
        $categories = [];
        $reasons = [];
        $confidence = 0;
        $providers = [];

        foreach ($results as $provider => $result) {
            if ($result['flagged'] ?? false) {
                $flagged = true;
            }
            
            if (isset($result['categories'])) {
                foreach ($result['categories'] as $category => $score) {
                    if (!isset($categories[$category])) {
                        $categories[$category] = 0;
                    }
                    $categories[$category] = max($categories[$category], $score);
                }
            }
            
            if (isset($result['reason'])) {
                $reasons[] = $result['reason'];
            }
            
            if (isset($result['confidence'])) {
                $confidence = max($confidence, $result['confidence']);
            }
            
            $providers[] = $provider;
        }

        // Check against thresholds
        $thresholds = $this->moderationConfig['thresholds'];
        foreach ($categories as $category => $score) {
            if (isset($thresholds[$category]) && $score >= $thresholds[$category]) {
                $flagged = true;
            }
        }

        return [
            'flagged' => $flagged,
            'categories' => $categories,
            'reasons' => $reasons,
            'confidence' => $confidence,
            'providers' => $providers,
            'action' => $this->determineAction($flagged, $categories),
            'content_length' => strlen($content),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Calculate confidence score
     */
    private function calculateConfidence(array $categories): float
    {
        if (empty($categories)) {
            return 0.0;
        }
        
        $scores = array_values($categories);
        return array_sum($scores) / count($scores);
    }

    /**
     * Determine moderation action
     */
    private function determineAction(bool $flagged, array $categories): string
    {
        if (!$flagged) {
            return 'approve';
        }

        $maxScore = max($categories);
        
        if ($maxScore >= 0.9) {
            return 'reject'; // High confidence - reject
        } elseif ($maxScore >= 0.7) {
            return 'review'; // Medium confidence - human review
        } else {
            return 'flag'; // Low confidence - flag for monitoring
        }
    }

    /**
     * Get moderation statistics
     */
    public function getModerationStats(): array
    {
        return Cache::remember('moderation_stats', 3600, function () {
            // This would typically query your database for moderation statistics
            return [
                'total_moderated' => 0,
                'flagged_count' => 0,
                'approved_count' => 0,
                'rejected_count' => 0,
                'review_count' => 0,
                'top_categories' => [],
                'average_confidence' => 0.0,
            ];
        });
    }
}
