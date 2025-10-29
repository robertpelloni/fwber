<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContentOptimizationService
{
    private string $openaiApiKey;
    private string $geminiApiKey;
    private array $optimizationConfig;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->geminiApiKey = config('services.gemini.api_key');
        $this->optimizationConfig = config('content_optimization', [
            'enabled' => true,
            'providers' => ['openai', 'gemini'],
            'optimization_types' => [
                'engagement' => 0.4,
                'clarity' => 0.3,
                'safety' => 0.2,
                'relevance' => 0.1,
            ],
            'cache_ttl' => 3600,
        ]);
    }

    /**
     * Optimize content for better engagement
     */
    public function optimizeContent(string $content, array $context = []): array
    {
        $cacheKey = 'optimization_' . md5($content . serialize($context));
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $optimizations = [];
        
        // Engagement optimization
        $optimizations['engagement'] = $this->optimizeForEngagement($content, $context);
        
        // Clarity optimization
        $optimizations['clarity'] = $this->optimizeForClarity($content, $context);
        
        // Safety optimization
        $optimizations['safety'] = $this->optimizeForSafety($content, $context);
        
        // Relevance optimization
        $optimizations['relevance'] = $this->optimizeForRelevance($content, $context);
        
        // Combine optimizations
        $result = $this->combineOptimizations($optimizations, $content);
        
        Cache::put($cacheKey, $result, $this->optimizationConfig['cache_ttl']);
        
        return $result;
    }

    /**
     * Optimize content for engagement
     */
    private function optimizeForEngagement(string $content, array $context): array
    {
        try {
            $prompt = "Optimize this content for maximum engagement:\n\n" .
                     "Content: \"$content\"\n" .
                     "Context: " . json_encode($context) . "\n\n" .
                     "Make it more engaging, interesting, and likely to get responses. " .
                     "Keep the original meaning but make it more compelling.";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert content optimizer specializing in social media engagement.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $optimizedContent = $data['choices'][0]['message']['content'] ?? '';
                
                return [
                    'original' => $content,
                    'optimized' => $optimizedContent,
                    'improvements' => $this->analyzeImprovements($content, $optimizedContent),
                    'engagement_score' => $this->calculateEngagementScore($optimizedContent),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Engagement optimization failed', ['error' => $e->getMessage()]);
        }

        return ['error' => 'Engagement optimization unavailable'];
    }

    /**
     * Optimize content for clarity
     */
    private function optimizeForClarity(string $content, array $context): array
    {
        try {
            $prompt = "Improve the clarity and readability of this content:\n\n" .
                     "Content: \"$content\"\n" .
                     "Context: " . json_encode($context) . "\n\n" .
                     "Make it clearer, more concise, and easier to understand. " .
                     "Fix any grammar or structure issues.";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert editor specializing in clear, concise communication.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $optimizedContent = $data['choices'][0]['message']['content'] ?? '';
                
                return [
                    'original' => $content,
                    'optimized' => $optimizedContent,
                    'improvements' => $this->analyzeClarityImprovements($content, $optimizedContent),
                    'clarity_score' => $this->calculateClarityScore($optimizedContent),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Clarity optimization failed', ['error' => $e->getMessage()]);
        }

        return ['error' => 'Clarity optimization unavailable'];
    }

    /**
     * Optimize content for safety
     */
    private function optimizeForSafety(string $content, array $context): array
    {
        $moderationService = app(ContentModerationService::class);
        $moderationResult = $moderationService->moderateContent($content);
        
        if (!$moderationResult['flagged']) {
            return ['status' => 'safe', 'content' => $content];
        }
        
        try {
            $prompt = "Make this content safer and more appropriate while keeping the original intent:\n\n" .
                     "Content: \"$content\"\n" .
                     "Issues: " . json_encode($moderationResult['categories']) . "\n\n" .
                     "Rewrite to be safe and appropriate for a social platform.";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a content safety expert. Make content appropriate while preserving intent.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $optimizedContent = $data['choices'][0]['message']['content'] ?? '';
                
                // Re-check safety
                $newModerationResult = $moderationService->moderateContent($optimizedContent);
                
                return [
                    'original' => $content,
                    'optimized' => $optimizedContent,
                    'safety_improvements' => $this->analyzeSafetyImprovements($moderationResult, $newModerationResult),
                    'safety_score' => $this->calculateSafetyScore($newModerationResult),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Safety optimization failed', ['error' => $e->getMessage()]);
        }

        return ['error' => 'Safety optimization unavailable'];
    }

    /**
     * Optimize content for relevance
     */
    private function optimizeForRelevance(string $content, array $context): array
    {
        try {
            $prompt = "Make this content more relevant to the context and audience:\n\n" .
                     "Content: \"$content\"\n" .
                     "Context: " . json_encode($context) . "\n\n" .
                     "Adjust the content to be more relevant to the location, community, and current trends.";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a content relevance expert. Make content more contextually relevant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $optimizedContent = $data['choices'][0]['message']['content'] ?? '';
                
                return [
                    'original' => $content,
                    'optimized' => $optimizedContent,
                    'relevance_improvements' => $this->analyzeRelevanceImprovements($content, $optimizedContent, $context),
                    'relevance_score' => $this->calculateRelevanceScore($optimizedContent, $context),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Relevance optimization failed', ['error' => $e->getMessage()]);
        }

        return ['error' => 'Relevance optimization unavailable'];
    }

    /**
     * Combine all optimizations
     */
    private function combineOptimizations(array $optimizations, string $originalContent): array
    {
        $finalOptimization = $originalContent;
        $allImprovements = [];
        $totalScore = 0;
        $validOptimizations = 0;

        foreach ($optimizations as $type => $optimization) {
            if (isset($optimization['optimized']) && !isset($optimization['error'])) {
                $finalOptimization = $optimization['optimized'];
                $allImprovements[$type] = $optimization['improvements'] ?? [];
                
                $scoreKey = $type . '_score';
                if (isset($optimization[$scoreKey])) {
                    $totalScore += $optimization[$scoreKey];
                    $validOptimizations++;
                }
            }
        }

        return [
            'original' => $originalContent,
            'optimized' => $finalOptimization,
            'improvements' => $allImprovements,
            'overall_score' => $validOptimizations > 0 ? $totalScore / $validOptimizations : 0,
            'optimization_types' => array_keys($optimizations),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Analyze improvements between original and optimized content
     */
    private function analyzeImprovements(string $original, string $optimized): array
    {
        $improvements = [];
        
        // Length improvement
        $originalLength = strlen($original);
        $optimizedLength = strlen($optimized);
        $improvements['length_change'] = $optimizedLength - $originalLength;
        
        // Word count improvement
        $originalWords = str_word_count($original);
        $optimizedWords = str_word_count($optimized);
        $improvements['word_count_change'] = $optimizedWords - $originalWords;
        
        // Readability improvement
        $originalReadability = $this->calculateReadabilityScore($original);
        $optimizedReadability = $this->calculateReadabilityScore($optimized);
        $improvements['readability_improvement'] = $optimizedReadability - $originalReadability;
        
        // Engagement indicators
        $engagementIndicators = ['?', '!', '😊', '😄', '👍', '❤️'];
        $originalEngagement = 0;
        $optimizedEngagement = 0;
        
        foreach ($engagementIndicators as $indicator) {
            $originalEngagement += substr_count($original, $indicator);
            $optimizedEngagement += substr_count($optimized, $indicator);
        }
        
        $improvements['engagement_indicators_change'] = $optimizedEngagement - $originalEngagement;
        
        return $improvements;
    }

    /**
     * Analyze clarity improvements
     */
    private function analyzeClarityImprovements(string $original, string $optimized): array
    {
        $improvements = [];
        
        // Sentence count
        $originalSentences = preg_split('/[.!?]+/', $original);
        $optimizedSentences = preg_split('/[.!?]+/', $optimized);
        $improvements['sentence_count_change'] = count($optimizedSentences) - count($originalSentences);
        
        // Average sentence length
        $originalAvgLength = $this->calculateAverageSentenceLength($original);
        $optimizedAvgLength = $this->calculateAverageSentenceLength($optimized);
        $improvements['sentence_length_change'] = $optimizedAvgLength - $originalAvgLength;
        
        // Complex word usage
        $originalComplexWords = $this->countComplexWords($original);
        $optimizedComplexWords = $this->countComplexWords($optimized);
        $improvements['complex_words_change'] = $optimizedComplexWords - $originalComplexWords;
        
        return $improvements;
    }

    /**
     * Analyze safety improvements
     */
    private function analyzeSafetyImprovements(array $original, array $optimized): array
    {
        $improvements = [];
        
        $originalFlagged = $original['flagged'] ?? false;
        $optimizedFlagged = $optimized['flagged'] ?? false;
        
        $improvements['flagged_status_change'] = $originalFlagged && !$optimizedFlagged ? 'improved' : 'no_change';
        
        if (isset($original['categories']) && isset($optimized['categories'])) {
            foreach ($original['categories'] as $category => $score) {
                $originalScore = $score;
                $optimizedScore = $optimized['categories'][$category] ?? 0;
                $improvements[$category . '_score_change'] = $optimizedScore - $originalScore;
            }
        }
        
        return $improvements;
    }

    /**
     * Analyze relevance improvements
     */
    private function analyzeRelevanceImprovements(string $original, string $optimized, array $context): array
    {
        $improvements = [];
        
        // Context keyword usage
        $contextKeywords = $this->extractContextKeywords($context);
        $originalRelevance = $this->calculateKeywordRelevance($original, $contextKeywords);
        $optimizedRelevance = $this->calculateKeywordRelevance($optimized, $contextKeywords);
        
        $improvements['context_relevance_change'] = $optimizedRelevance - $originalRelevance;
        
        // Location relevance
        if (isset($context['location'])) {
            $originalLocationRelevance = $this->calculateLocationRelevance($original, $context['location']);
            $optimizedLocationRelevance = $this->calculateLocationRelevance($optimized, $context['location']);
            $improvements['location_relevance_change'] = $optimizedLocationRelevance - $originalLocationRelevance;
        }
        
        return $improvements;
    }

    /**
     * Calculate engagement score
     */
    private function calculateEngagementScore(string $content): float
    {
        $score = 0;
        
        // Question marks (encourage responses)
        $score += substr_count($content, '?') * 0.1;
        
        // Exclamation marks (show enthusiasm)
        $score += substr_count($content, '!') * 0.05;
        
        // Emojis (add personality)
        $emojiCount = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', $content);
        $score += $emojiCount * 0.05;
        
        // Call-to-action words
        $ctaWords = ['join', 'come', 'let\'s', 'share', 'tell', 'show', 'meet'];
        foreach ($ctaWords as $word) {
            $score += substr_count(strtolower($content), $word) * 0.1;
        }
        
        return min(1.0, $score);
    }

    /**
     * Calculate clarity score
     */
    private function calculateClarityScore(string $content): float
    {
        $readabilityScore = $this->calculateReadabilityScore($content);
        $sentenceLengthScore = $this->calculateSentenceLengthScore($content);
        $vocabularyScore = $this->calculateVocabularyScore($content);
        
        return ($readabilityScore + $sentenceLengthScore + $vocabularyScore) / 3;
    }

    /**
     * Calculate safety score
     */
    private function calculateSafetyScore(array $moderationResult): float
    {
        if ($moderationResult['flagged']) {
            return 0.0;
        }
        
        $categories = $moderationResult['categories'] ?? [];
        if (empty($categories)) {
            return 1.0;
        }
        
        $maxScore = max($categories);
        return 1.0 - $maxScore;
    }

    /**
     * Calculate relevance score
     */
    private function calculateRelevanceScore(string $content, array $context): float
    {
        $score = 0;
        
        // Context keyword relevance
        $contextKeywords = $this->extractContextKeywords($context);
        $score += $this->calculateKeywordRelevance($content, $contextKeywords) * 0.5;
        
        // Location relevance
        if (isset($context['location'])) {
            $score += $this->calculateLocationRelevance($content, $context['location']) * 0.3;
        }
        
        // Time relevance
        if (isset($context['time'])) {
            $score += $this->calculateTimeRelevance($content, $context['time']) * 0.2;
        }
        
        return min(1.0, $score);
    }

    /**
     * Calculate readability score using Flesch Reading Ease
     */
    private function calculateReadabilityScore(string $content): float
    {
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $syllables = $this->countSyllables($content);
        
        if (count($sentences) == 0 || $words == 0) {
            return 0.0;
        }
        
        $avgWordsPerSentence = $words / count($sentences);
        $avgSyllablesPerWord = $syllables / $words;
        
        // Flesch Reading Ease Score
        $score = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
        
        return max(0.0, min(1.0, $score / 100));
    }

    /**
     * Calculate sentence length score
     */
    private function calculateSentenceLengthScore(string $content): float
    {
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $sentenceCount = count(array_filter($sentences));
        
        if ($sentenceCount == 0) return 0.5;
        
        $avgWordsPerSentence = $words / $sentenceCount;
        
        // Optimal range is 15-20 words per sentence
        if ($avgWordsPerSentence >= 15 && $avgWordsPerSentence <= 20) {
            return 1.0;
        } elseif ($avgWordsPerSentence < 15) {
            return $avgWordsPerSentence / 15;
        } else {
            return max(0.0, 1.0 - (($avgWordsPerSentence - 20) / 20));
        }
    }

    /**
     * Calculate vocabulary score
     */
    private function calculateVocabularyScore(string $content): float
    {
        $words = str_word_count($content, 1);
        $totalWords = count($words);
        
        if ($totalWords == 0) return 0.0;
        
        $uniqueWords = count(array_unique($words));
        $vocabularyDiversity = $uniqueWords / $totalWords;
        
        return min(1.0, $vocabularyDiversity * 2);
    }

    /**
     * Calculate average sentence length
     */
    private function calculateAverageSentenceLength(string $content): float
    {
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $sentenceCount = count(array_filter($sentences));
        
        return $sentenceCount > 0 ? $words / $sentenceCount : 0;
    }

    /**
     * Count complex words (longer than 6 characters)
     */
    private function countComplexWords(string $content): int
    {
        $words = str_word_count($content, 1);
        $complexWords = 0;
        
        foreach ($words as $word) {
            if (strlen($word) > 6) {
                $complexWords++;
            }
        }
        
        return $complexWords;
    }

    /**
     * Count syllables in text
     */
    private function countSyllables(string $text): int
    {
        $words = str_word_count($text, 1);
        $syllables = 0;
        
        foreach ($words as $word) {
            $syllables += $this->countWordSyllables($word);
        }
        
        return $syllables;
    }

    /**
     * Count syllables in a single word
     */
    private function countWordSyllables(string $word): int
    {
        $word = strtolower($word);
        $syllables = 0;
        $vowels = 'aeiouy';
        $prevChar = '';
        
        for ($i = 0; $i < strlen($word); $i++) {
            $char = $word[$i];
            if (strpos($vowels, $char) !== false && strpos($vowels, $prevChar) === false) {
                $syllables++;
            }
            $prevChar = $char;
        }
        
        // Handle silent 'e'
        if (substr($word, -1) === 'e' && $syllables > 1) {
            $syllables--;
        }
        
        return max(1, $syllables);
    }

    /**
     * Extract context keywords
     */
    private function extractContextKeywords(array $context): array
    {
        $keywords = [];
        
        if (isset($context['interests'])) {
            $keywords = array_merge($keywords, $context['interests']);
        }
        
        if (isset($context['topics'])) {
            $keywords = array_merge($keywords, $context['topics']);
        }
        
        if (isset($context['location']['name'])) {
            $keywords[] = $context['location']['name'];
        }
        
        return array_unique($keywords);
    }

    /**
     * Calculate keyword relevance
     */
    private function calculateKeywordRelevance(string $content, array $keywords): float
    {
        if (empty($keywords)) return 0.5;
        
        $matches = 0;
        $contentLower = strtolower($content);
        
        foreach ($keywords as $keyword) {
            if (strpos($contentLower, strtolower($keyword)) !== false) {
                $matches++;
            }
        }
        
        return $matches / count($keywords);
    }

    /**
     * Calculate location relevance
     */
    private function calculateLocationRelevance(string $content, array $location): float
    {
        $locationKeywords = [];
        
        if (isset($location['name'])) {
            $locationKeywords[] = $location['name'];
        }
        
        if (isset($location['city'])) {
            $locationKeywords[] = $location['city'];
        }
        
        if (isset($location['neighborhood'])) {
            $locationKeywords[] = $location['neighborhood'];
        }
        
        return $this->calculateKeywordRelevance($content, $locationKeywords);
    }

    /**
     * Calculate time relevance
     */
    private function calculateTimeRelevance(string $content, string $time): float
    {
        $timeKeywords = [];
        
        $hour = (int) date('H', strtotime($time));
        
        if ($hour >= 6 && $hour < 12) {
            $timeKeywords = ['morning', 'breakfast', 'coffee', 'start', 'early'];
        } elseif ($hour >= 12 && $hour < 18) {
            $timeKeywords = ['afternoon', 'lunch', 'work', 'day', 'sunny'];
        } elseif ($hour >= 18 && $hour < 22) {
            $timeKeywords = ['evening', 'dinner', 'sunset', 'relax', 'wind down'];
        } else {
            $timeKeywords = ['night', 'late', 'nightlife', 'party', 'stars'];
        }
        
        return $this->calculateKeywordRelevance($content, $timeKeywords);
    }
}
