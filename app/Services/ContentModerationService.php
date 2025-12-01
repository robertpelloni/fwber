<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Ai\Llm\LlmManager;

class ContentModerationService
{
    private LlmManager $llmManager;
    private array $moderationConfig;

    public function __construct(LlmManager $llmManager)
    {
        $this->llmManager = $llmManager;
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
        
        // OpenAI moderation
        if (in_array('openai', $this->moderationConfig['providers'])) {
            $results['openai'] = $this->moderateWithOpenAI($content);
        }
        
        // Gemini moderation
        if (in_array('gemini', $this->moderationConfig['providers'])) {
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
            $result = $this->llmManager->driver('openai')->moderate($content);
            
            if (isset($result['error'])) {
                return ['flagged' => false, 'error' => $result['error']];
            }

            return [
                'flagged' => $result['flagged'],
                'categories' => $result['categories'],
                'confidence' => $result['score'],
                'provider' => 'openai'
            ];
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
            // Note: Context is currently ignored in driver implementation for simplicity, 
            // but could be appended to content if needed.
            $result = $this->llmManager->driver('gemini')->moderate($content);
            
            if (isset($result['error'])) {
                return ['flagged' => false, 'error' => $result['error']];
            }

            return [
                'flagged' => $result['flagged'],
                'categories' => $result['categories'],
                'confidence' => $result['score'],
                'provider' => 'gemini'
            ];
        } catch (\Exception $e) {
            Log::error('Gemini moderation failed', ['error' => $e->getMessage()]);
        }

        return ['flagged' => false, 'error' => 'Gemini moderation unavailable'];
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
