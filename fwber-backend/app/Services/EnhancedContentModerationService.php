<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ContentModerationService;

class EnhancedContentModerationService
{
    private ContentModerationService $baseModeration;
    private array $safetyConfig;
    private array $forbiddenPatterns;

    public function __construct()
    {
        $this->baseModeration = app(ContentModerationService::class);
        $this->safetyConfig = config('content_safety', [
            'enabled' => true,
            'human_review_threshold' => 0.7,
            'auto_approve_threshold' => 0.9,
            'block_threshold' => 0.95,
            'cache_ttl' => 3600,
        ]);

        $this->forbiddenPatterns = [
            '/\b(?:kill|murder|suicide|self.?harm)\b/i',
            '/\b(?:bomb|explosive|weapon|gun|knife)\b/i',
            '/\b(?:drug|heroin|cocaine|meth)\b/i',
            '/\b(?:hate|racist|sexist|homophobic)\b/i',
            '/\b(?:spam|scam|phishing|fraud)\b/i',
            '/\b(?:underage|minor|child)\b/i',
            '/\b(?:prostitution|escort|sex.?work)\b/i',
            '/\b(?:violence|assault|rape)\b/i',
        ];
    }

    /**
     * Moderate content with enhanced safety checks
     */
    public function moderateContent(string $content, array $context = []): array
    {
        $cacheKey = 'enhanced_moderation_' . md5($content);
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Pre-processing safety checks
        $preCheck = $this->preProcessContent($content);
        if ($preCheck['blocked']) {
            $result = array_merge($preCheck, [
                'provider' => 'pre_processing',
                'timestamp' => now()->toISOString(),
            ]);
            
            Cache::put($cacheKey, $result, $this->safetyConfig['cache_ttl']);
            return $result;
        }

        // Multi-AI moderation
        $results = $this->baseModeration->moderateContent($content, $context);
        
        // Post-processing safety analysis
        $safetyAnalysis = $this->analyzeSafetyScore($results);
        
        // Combine results
        $finalResult = array_merge($results, $safetyAnalysis, [
            'enhanced_moderation' => true,
            'timestamp' => now()->toISOString(),
        ]);

        // Cache result
        Cache::put($cacheKey, $finalResult, $this->safetyConfig['cache_ttl']);
        
        return $finalResult;
    }

    /**
     * Moderate content with specific safety focus
     */
    public function moderateWithSafetyFocus(string $content, string $safetyFocus, array $context = []): array
    {
        $focusConfigs = [
            'dating_safety' => [
                'patterns' => [
                    '/\b(?:hookup|one.?night|casual.?sex)\b/i',
                    '/\b(?:sugar.?daddy|sugar.?mommy)\b/i',
                    '/\b(?:escort|prostitute|sex.?work)\b/i',
                ],
                'weight' => 0.8,
            ],
            'location_safety' => [
                'patterns' => [
                    '/\b(?:meet.?up|come.?over|my.?place|your.?place)\b/i',
                    '/\b(?:address|location|where.?are.?you)\b/i',
                    '/\b(?:alone|private|secret)\b/i',
                ],
                'weight' => 0.6,
            ],
            'financial_safety' => [
                'patterns' => [
                    '/\b(?:money|cash|payment|loan|debt)\b/i',
                    '/\b(?:investment|bitcoin|crypto|scam)\b/i',
                    '/\b(?:rich|wealthy|millionaire)\b/i',
                ],
                'weight' => 0.7,
            ],
        ];

        $focusConfig = $focusConfigs[$safetyFocus] ?? $focusConfigs['dating_safety'];
        
        $baseResult = $this->moderateContent($content, $context);
        
        // Apply safety focus weighting
        $focusedResult = $this->applySafetyFocus($baseResult, $focusConfig);
        
        return $focusedResult;
    }

    /**
     * Get content safety score breakdown
     */
    public function getSafetyScoreBreakdown(string $content): array
    {
        $breakdown = [
            'overall_score' => 0,
            'categories' => [],
            'recommendations' => [],
        ];

        // Check each safety category
        $categories = [
            'hate_speech' => $this->checkHateSpeech($content),
            'violence' => $this->checkViolence($content),
            'sexual_content' => $this->checkSexualContent($content),
            'spam' => $this->checkSpam($content),
            'personal_info' => $this->checkPersonalInfo($content),
            'dating_safety' => $this->checkDatingSafety($content),
        ];

        $totalScore = 0;
        $categoryCount = 0;

        foreach ($categories as $category => $score) {
            $breakdown['categories'][$category] = $score;
            $totalScore += $score;
            $categoryCount++;
        }

        $breakdown['overall_score'] = $categoryCount > 0 ? $totalScore / $categoryCount : 0;
        $breakdown['recommendations'] = $this->generateSafetyRecommendations($breakdown);

        return $breakdown;
    }

    /**
     * Pre-process content for obvious violations
     */
    private function preProcessContent(string $content): array
    {
        // Check for forbidden patterns
        foreach ($this->forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return [
                    'blocked' => true,
                    'reason' => 'Forbidden content pattern detected',
                    'confidence' => 1.0,
                    'category' => 'pre_processing',
                ];
            }
        }

        // Check for excessive repetition (spam)
        if ($this->detectSpamPatterns($content)) {
            return [
                'blocked' => true,
                'reason' => 'Spam pattern detected',
                'confidence' => 0.9,
                'category' => 'spam',
            ];
        }

        // Check for personal information
        if ($this->detectPersonalInfo($content)) {
            return [
                'blocked' => true,
                'reason' => 'Personal information detected',
                'confidence' => 0.8,
                'category' => 'privacy',
            ];
        }

        return ['blocked' => false];
    }

    /**
     * Analyze safety score from moderation results
     */
    private function analyzeSafetyScore(array $results): array
    {
        $safetyScore = $this->calculateSafetyScore($results);
        
        return [
            'safety_score' => $safetyScore,
            'requires_human_review' => $safetyScore < $this->safetyConfig['human_review_threshold'],
            'auto_approve' => $safetyScore > $this->safetyConfig['auto_approve_threshold'],
            'block_content' => $safetyScore < $this->safetyConfig['block_threshold'],
            'safety_level' => $this->getSafetyLevel($safetyScore),
        ];
    }

    /**
     * Calculate overall safety score
     */
    private function calculateSafetyScore(array $results): float
    {
        if (isset($results['categories'])) {
            $scores = array_values($results['categories']);
            return array_sum($scores) / count($scores);
        }

        return $results['confidence'] ?? 0.5;
    }

    /**
     * Get safety level based on score
     */
    private function getSafetyLevel(float $score): string
    {
        if ($score >= 0.9) return 'safe';
        if ($score >= 0.7) return 'moderate';
        if ($score >= 0.5) return 'risky';
        return 'unsafe';
    }

    /**
     * Apply safety focus weighting
     */
    private function applySafetyFocus(array $results, array $focusConfig): array
    {
        $weightedScore = $results['safety_score'] * $focusConfig['weight'];
        
        return array_merge($results, [
            'focused_safety_score' => $weightedScore,
            'focus_weight' => $focusConfig['weight'],
            'focus_applied' => true,
        ]);
    }

    /**
     * Check for hate speech
     */
    private function checkHateSpeech(string $content): float
    {
        $hatePatterns = [
            '/\b(?:racist|sexist|homophobic|transphobic)\b/i',
            '/\b(?:hate|disgusting|gross|disgusting)\b/i',
            '/\b(?:kill|destroy|eliminate)\b/i',
        ];

        $matches = 0;
        foreach ($hatePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.3));
    }

    /**
     * Check for violence
     */
    private function checkViolence(string $content): float
    {
        $violencePatterns = [
            '/\b(?:violence|assault|attack|fight)\b/i',
            '/\b(?:weapon|gun|knife|bomb)\b/i',
            '/\b(?:hurt|harm|injure|damage)\b/i',
        ];

        $matches = 0;
        foreach ($violencePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.4));
    }

    /**
     * Check for sexual content
     */
    private function checkSexualContent(string $content): float
    {
        $sexualPatterns = [
            '/\b(?:sex|sexual|intimate|nude)\b/i',
            '/\b(?:hookup|one.?night|casual)\b/i',
            '/\b(?:escort|prostitute|sex.?work)\b/i',
        ];

        $matches = 0;
        foreach ($sexualPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.2));
    }

    /**
     * Check for spam
     */
    private function checkSpam(string $content): float
    {
        $spamPatterns = [
            '/\b(?:spam|scam|phishing|fraud)\b/i',
            '/\b(?:click.?here|buy.?now|limited.?time)\b/i',
            '/\b(?:free.?money|get.?rich|investment)\b/i',
        ];

        $matches = 0;
        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.3));
    }

    /**
     * Check for personal information
     */
    private function checkPersonalInfo(string $content): float
    {
        $personalInfoPatterns = [
            '/\b\d{3}-\d{3}-\d{4}\b/', // Phone number
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\d{5}(-\d{4})?\b/', // ZIP code
            '/\b(?:address|location|where.?are.?you)\b/i',
        ];

        $matches = 0;
        foreach ($personalInfoPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.5));
    }

    /**
     * Check for dating safety
     */
    private function checkDatingSafety(string $content): float
    {
        $datingSafetyPatterns = [
            '/\b(?:meet.?up|come.?over|my.?place)\b/i',
            '/\b(?:alone|private|secret)\b/i',
            '/\b(?:money|cash|payment)\b/i',
            '/\b(?:underage|minor|young)\b/i',
        ];

        $matches = 0;
        foreach ($datingSafetyPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return max(0, 1 - ($matches * 0.3));
    }

    /**
     * Detect spam patterns
     */
    private function detectSpamPatterns(string $content): bool
    {
        // Check for excessive repetition
        $words = explode(' ', strtolower($content));
        $wordCounts = array_count_values($words);
        
        foreach ($wordCounts as $word => $count) {
            if ($count > 5 && strlen($word) > 3) {
                return true;
            }
        }

        // Check for excessive punctuation
        if (substr_count($content, '!') > 5 || substr_count($content, '?') > 5) {
            return true;
        }

        return false;
    }

    /**
     * Detect personal information
     */
    private function detectPersonalInfo(string $content): bool
    {
        $personalInfoPatterns = [
            '/\b\d{3}-\d{3}-\d{4}\b/', // Phone number
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\d{5}(-\d{4})?\b/', // ZIP code
        ];

        foreach ($personalInfoPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate safety recommendations
     */
    private function generateSafetyRecommendations(array $breakdown): array
    {
        $recommendations = [];

        if ($breakdown['overall_score'] < 0.5) {
            $recommendations[] = 'Content appears unsafe and should be reviewed by a human moderator';
        }

        if ($breakdown['categories']['hate_speech'] < 0.7) {
            $recommendations[] = 'Content may contain hate speech - consider rewriting';
        }

        if ($breakdown['categories']['violence'] < 0.7) {
            $recommendations[] = 'Content may contain violent language - consider toning down';
        }

        if ($breakdown['categories']['personal_info'] < 0.7) {
            $recommendations[] = 'Content may contain personal information - consider removing';
        }

        if ($breakdown['categories']['dating_safety'] < 0.7) {
            $recommendations[] = 'Content may not be safe for dating context - consider revising';
        }

        return $recommendations;
    }
}
