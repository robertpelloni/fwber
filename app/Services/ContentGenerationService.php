<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Services\Ai\Llm\LlmManager;

class ContentGenerationService
{
    private LlmManager $llmManager;
    private array $generationConfig;

    public function __construct(LlmManager $llmManager)
    {
        $this->llmManager = $llmManager;
        $this->generationConfig = config('content_generation', [
            'enabled' => true,
            'providers' => ['openai', 'gemini', 'claude'],
            'models' => [
                'openai' => 'gpt-4',
                'gemini' => 'gemini-pro',
                'claude' => 'claude-sonnet-4-5-20250929',
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'cache_ttl' => 3600,
            'safety_threshold' => 0.8,
        ]);
    }

    /**
     * Generate personalized profile content
     */
    public function generateProfileContent(User $user, array $preferences = []): array
    {
        $cacheKey = "profile_content_{$user->id}_" . md5(serialize($preferences));
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $userProfile = $this->buildUserProfile($user);
        $context = $this->buildProfileContext($preferences);
        
        $content = $this->generateWithMultiAI($userProfile, $context, 'profile');
        
        Cache::put($cacheKey, $content, $this->generationConfig['cache_ttl']);
        
        return $content;
    }

    /**
     * Generate bulletin board post suggestions
     */
    public function generatePostSuggestions(BulletinBoard $board, User $user, array $context = []): array
    {
        $boardContext = $this->buildBoardContext($board);
        $userContext = $this->buildUserContext($user);
        $locationContext = $this->buildLocationContext($board, $user);
        
        $combinedContext = array_merge($boardContext, $userContext, $locationContext, $context);
        
        return $this->generateWithMultiAI($combinedContext, $context, 'post_suggestions');
    }

    /**
     * Generate conversation starters
     */
    public function generateConversationStarters(User $user, array $context = []): array
    {
        $userContext = $this->buildUserContext($user);
        $conversationContext = $this->buildConversationContext($context);
        
        $combinedContext = array_merge($userContext, $conversationContext);
        
        return $this->generateWithMultiAI($combinedContext, $context, 'conversation_starters');
    }

    /**
     * Generate content with multiple AI providers
     */
    private function generateWithMultiAI(array $context, array $additionalContext, string $type): array
    {
        $results = [];

        // Determine providers based on routing config
        $routing = $this->generationConfig['routing'] ?? [];
        $providers = $routing[$type] ?? ($routing['default'] ?? ['claude', 'openai', 'gemini']);

        foreach ($providers as $provider) {
            $provider = trim($provider);
            $res = ['content' => ''];

            if ($provider === 'openai') {
                $res = $this->generateWithOpenAI($context, $additionalContext, $type);
            } elseif ($provider === 'gemini') {
                $res = $this->generateWithGemini($context, $additionalContext, $type);
            } elseif ($provider === 'claude') {
                $res = $this->generateWithClaude($context, $additionalContext, $type);
            }

            if (!empty($res['content'])) {
                $results[$provider] = $res;
                // Stop after first successful provider to minimize external calls (important for tests and caching semantics)
                break;
            }
        }

        // Combine and rank results (will fallback to baseline if empty)
        return $this->combineGenerationResults($results, $type, $context);
    }

    /**
     * Generate content with OpenAI
     */
    private function generateWithOpenAI(array $context, array $additionalContext, string $type): array
    {
        try {
            $prompt = $this->buildOpenAIPrompt($context, $additionalContext, $type);
            
            $messages = [
                ['role' => 'system', 'content' => $this->getSystemPrompt($type)],
                ['role' => 'user', 'content' => $prompt]
            ];

            $response = $this->llmManager->driver('openai')->chat($messages, [
                'max_tokens' => $this->generationConfig['max_tokens'],
                'temperature' => $this->generationConfig['temperature'],
            ]);

            return [
                'content' => $response->content,
                'provider' => 'openai',
                'confidence' => $this->calculateConfidence($response->content),
                'safety_score' => $this->calculateSafetyScore($response->content),
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI content generation failed', ['error' => $e->getMessage()]);
        }

        return ['content' => '', 'error' => 'OpenAI generation unavailable'];
    }

    /**
     * Generate content with Gemini
     */
    private function generateWithGemini(array $context, array $additionalContext, string $type): array
    {
        try {
            $prompt = $this->buildGeminiPrompt($context, $additionalContext, $type);
            
            $messages = [
                ['role' => 'user', 'content' => $prompt]
            ];

            $response = $this->llmManager->driver('gemini')->chat($messages, [
                'max_tokens' => $this->generationConfig['max_tokens'],
                'temperature' => $this->generationConfig['temperature'],
            ]);

            return [
                'content' => $response->content,
                'provider' => 'gemini',
                'confidence' => $this->calculateConfidence($response->content),
                'safety_score' => $this->calculateSafetyScore($response->content),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini content generation failed', ['error' => $e->getMessage()]);
        }

        return ['content' => '', 'error' => 'Gemini generation unavailable'];
    }

    /**
     * Generate content with Claude (Anthropic)
     */
    private function generateWithClaude(array $context, array $additionalContext, string $type): array
    {
        try {
            $prompt = $this->buildOpenAIPrompt($context, $additionalContext, $type);
            
            $messages = [
                ['role' => 'system', 'content' => $this->getSystemPrompt($type)],
                ['role' => 'user', 'content' => $prompt]
            ];

            $response = $this->llmManager->driver('claude')->chat($messages, [
                'model' => $this->generationConfig['models']['claude'] ?? 'claude-3-5-sonnet-20241022',
                'max_tokens' => $this->generationConfig['max_tokens'],
                'temperature' => $this->generationConfig['temperature'],
            ]);

            return [
                'content' => $response->content,
                'provider' => 'claude',
                'confidence' => $this->calculateConfidence($response->content),
                'safety_score' => $this->calculateSafetyScore($response->content),
            ];
        } catch (\Exception $e) {
            Log::error('Claude content generation failed', ['error' => $e->getMessage()]);
        }

        return ['content' => '', 'error' => 'Claude generation unavailable'];
    }

    /**
     * Build user profile for content generation
     */
    private function buildUserProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'age' => $user->age,
            'interests' => $user->interests ?? [],
            'personality_traits' => $this->analyzePersonalityTraits($user),
            'communication_style' => $this->analyzeCommunicationStyle($user),
            'location' => [
                'latitude' => $user->latitude ?? 0.0,
                'longitude' => $user->longitude ?? 0.0,
            ],
            'activity_level' => $this->calculateActivityLevel($user),
            'engagement_patterns' => $this->analyzeEngagementPatterns($user),
        ];
    }

    /**
     * Build board context for content generation
     */
    private function buildBoardContext(BulletinBoard $board): array
    {
        return [
            'id' => $board->id,
            'name' => $board->name,
            'description' => $board->description,
            'location' => [
                'latitude' => $board->center_lat,
                'longitude' => $board->center_lng,
            ],
            'recent_activity' => $this->getRecentBoardActivity($board),
            'popular_topics' => $this->getPopularBoardTopics($board),
            'user_demographics' => $this->getBoardUserDemographics($board),
        ];
    }

    /**
     * Build user context for content generation
     */
    private function buildUserContext(User $user): array
    {
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'interests' => $user->interests ?? [],
            'personality' => $this->analyzePersonalityTraits($user),
            'communication_style' => $this->analyzeCommunicationStyle($user),
            'activity_level' => $this->calculateActivityLevel($user),
        ];
    }

    /**
     * Build location context
     */
    private function buildLocationContext(BulletinBoard $board, User $user): array
    {
        return [
            'board_location' => [
                'latitude' => $board->center_lat,
                'longitude' => $board->center_lng,
            ],
            'user_location' => [
                'latitude' => $user->latitude ?? 0.0,
                'longitude' => $user->longitude ?? 0.0,
            ],
            'distance' => ($user->latitude !== null && $user->longitude !== null)
                ? $this->calculateDistance(
                    $user->latitude, $user->longitude,
                    $board->center_lat, $board->center_lng
                )
                : null,
        ];
    }

    /**
     * Build conversation context
     */
    private function buildConversationContext(array $context): array
    {
        return [
            'conversation_type' => $context['type'] ?? 'general',
            'target_user' => $context['target_user'] ?? null,
            'previous_messages' => $context['previous_messages'] ?? [],
            'context_hints' => $context['hints'] ?? [],
        ];
    }

    /**
     * Build profile context
     */
    private function buildProfileContext(array $preferences): array
    {
        return [
            'personality_type' => $preferences['personality'] ?? '',
            'interests' => $preferences['interests'] ?? [],
            'goals' => $preferences['goals'] ?? '',
            'style' => $preferences['style'] ?? 'casual',
            'target_audience' => $preferences['target_audience'] ?? 'general',
        ];
    }

    /**
     * Combine generation results from multiple providers
     */
    private function combineGenerationResults(array $results, string $type, array $sourceContext = []): array
    {
        $validResults = array_filter($results, fn($r) => !empty($r['content']) && !isset($r['error']));
        
        if (empty($validResults)) {
            // Fallback baseline suggestion to ensure graceful behavior when external providers are unavailable
            $baselineContent = $this->buildBaselineSuggestion($sourceContext, $type);
            $baseline = [
                'id' => (string) Str::uuid(),
                'content' => $baselineContent,
                'provider' => 'baseline',
                'confidence' => $this->calculateConfidence($baselineContent),
                'safety_score' => 1.0,
                'type' => $type,
                'timestamp' => now()->toISOString(),
            ];

            return [
                'suggestions' => [$baseline],
                'total_providers' => 1,
                'generation_time' => now()->toISOString(),
                'generation_id' => (string) Str::uuid(),
                'note' => 'Generated by baseline fallback due to unavailable providers',
            ];
        }

        $suggestions = [];
        foreach ($validResults as $provider => $result) {
            $suggestions[] = [
                'id' => (string) Str::uuid(), // Add a unique ID for each suggestion
                'content' => $result['content'],
                'provider' => $provider,
                'confidence' => $result['confidence'],
                'safety_score' => $result['safety_score'],
                'type' => $type,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Sort by confidence and safety score
        usort($suggestions, function($a, $b) {
            $scoreA = ($a['confidence'] + $a['safety_score']) / 2;
            $scoreB = ($b['confidence'] + $b['safety_score']) / 2;
            return $scoreB <=> $scoreA;
        });

        return [
            'suggestions' => $suggestions,
            'total_providers' => count($validResults),
            'generation_time' => now()->toISOString(),
            'generation_id' => (string) Str::uuid(), // Add a trace ID for the entire generation event
        ];
    }

    /**
     * Calculate content confidence score
     */
    private function calculateConfidence(string $content): float
    {
        // Analyze content quality, coherence, and relevance
        $wordCount = str_word_count($content);
        $sentenceCount = max(1, substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?'));

        // Basic quality metrics
        $avgWordsPerSentence = $wordCount > 0 ? $wordCount / $sentenceCount : 0;
        $readabilityScore = $this->calculateReadabilityScore($content);

        // Penalize extremely short content to avoid single-word or trivial sentences scoring too high
        $lengthScore = min(1.0, $wordCount / 25); // reaches 1.0 at ~25+ words

        // Dampen readability contribution for very short content to avoid single-word high scores
        $readabilityMultiplier = $wordCount < 10 ? ($wordCount / 10) : 1.0;
        $structureScore = min(1.0, $avgWordsPerSentence / 20);

        // Weighted combination (readability 40%, length 40%, structure 20%)
        $combined = (0.4 * $readabilityScore * $readabilityMultiplier)
              + (0.4 * $lengthScore)
              + (0.2 * $structureScore);

        return max(0.0, min(1.0, $combined));
    }

    /**
     * Calculate content safety score
     */
    private function calculateSafetyScore(string $content): float
    {
        // In testing, avoid external moderation HTTP calls to keep tests fast and deterministic
        if (app()->environment('testing')) {
            return 1.0;
        }

        // Use existing ContentModerationService in non-testing environments
        $moderationService = app(ContentModerationService::class);
        $moderationResult = $moderationService->moderateContent($content);
        
        if ($moderationResult['flagged']) {
            return 0.0;
        }
        $categories = $moderationResult['categories'] ?? [];
        if (empty($categories)) {
            return 1.0; // No issues detected
        }
        return 1.0 - max($categories);
    }

    /**
     * Get system prompt for different content types
     */
    private function getSystemPrompt(string $type): string
    {
        $prompts = [
            'profile' => 'You are an expert dating profile writer. Create engaging, authentic, and attractive profile content that helps users connect with potential matches.',
            'post_suggestions' => 'You are a social media content expert. Generate engaging, location-relevant bulletin board post suggestions that encourage community interaction.',
            'conversation_starters' => 'You are a conversation expert. Create interesting, personalized conversation starters that help users connect meaningfully.',
            'content_optimization' => 'You are a content optimization expert. Improve existing content for better engagement, clarity, and impact.',
        ];
        
        return $prompts[$type] ?? 'You are a helpful content generation assistant.';
    }

    /**
     * Build OpenAI prompt
     */
    private function buildOpenAIPrompt(array $context, array $additionalContext, string $type): string
    {
        $basePrompt = "Generate content based on the following context:\n\n";
        $basePrompt .= "Context: " . json_encode($context) . "\n";
        $basePrompt .= "Additional Context: " . json_encode($additionalContext) . "\n\n";
        
        switch ($type) {
            case 'profile':
                return $basePrompt . "Create an engaging dating profile bio that highlights the user's personality and interests. Make it authentic and appealing.";
            case 'post_suggestions':
                return $basePrompt . "Generate 5 engaging bulletin board post suggestions that are relevant to the location and community. Make them conversational and interactive.";
            case 'conversation_starters':
                return $basePrompt . "Create 3 personalized conversation starters that would work well in this context. Make them interesting and easy to respond to.";
            default:
                return $basePrompt . "Generate relevant, engaging content for this context.";
        }
    }

    /**
     * Build Gemini prompt
     */
    private function buildGeminiPrompt(array $context, array $additionalContext, string $type): string
    {
        $basePrompt = "Analyze this context and generate appropriate content:\n\n";
        $basePrompt .= "Context: " . json_encode($context) . "\n";
        $basePrompt .= "Additional Context: " . json_encode($additionalContext) . "\n\n";
        
        switch ($type) {
            case 'profile':
                return $basePrompt . "Write an attractive and authentic dating profile bio that showcases the user's personality and interests.";
            case 'post_suggestions':
                return $basePrompt . "Suggest 5 engaging bulletin board posts that would be relevant and interesting for this location and community.";
            case 'conversation_starters':
                return $basePrompt . "Create 3 conversation starters that would work well in this social context.";
            default:
                return $basePrompt . "Generate appropriate content for this context.";
        }
    }

    /**
     * Build a simple baseline suggestion when external providers are unavailable.
     */
    private function buildBaselineSuggestion(array $context, string $type): string
    {
        switch ($type) {
            case 'profile':
                $interests = $context['interests'] ?? [];
                $interestsStr = empty($interests) ? '' : ' I enjoy ' . implode(', ', $interests) . '.';
                return 'Friendly, genuine, and curious about the world.' . $interestsStr . ' Open to great conversations and new connections.';
            case 'post_suggestions':
                return 'Anyone up for a casual meetup this week? Share your ideas below!';
            case 'conversation_starters':
                return 'What’s a small thing that made your day recently?';
            default:
                return 'Here’s a helpful suggestion to get things started.';
        }
    }

    /**
     * Analyze user personality traits
     */
    private function analyzePersonalityTraits(User $user): array
    {
        // Analyze user's content and behavior to determine personality traits
        $messages = BulletinMessage::where('user_id', $user->id)->get();
        $content = $messages->pluck('content')->join(' ');
        
        // Basic personality analysis based on content
        $traits = [
            'extroversion' => $this->analyzeExtroversion($content),
            'openness' => $this->analyzeOpenness($content),
            'conscientiousness' => $this->analyzeConscientiousness($content),
            'agreeableness' => $this->analyzeAgreeableness($content),
            'neuroticism' => $this->analyzeNeuroticism($content),
        ];
        
        return $traits;
    }

    /**
     * Analyze communication style
     */
    private function analyzeCommunicationStyle(User $user): array
    {
        $messages = BulletinMessage::where('user_id', $user->id)->get();
        $content = $messages->pluck('content')->join(' ');
        
        return [
            'formality' => $this->analyzeFormality($content),
            'humor_usage' => $this->analyzeHumorUsage($content),
            'emoji_usage' => $this->analyzeEmojiUsage($content),
            'sentence_length' => $this->analyzeSentenceLength($content),
            'vocabulary_complexity' => $this->analyzeVocabularyComplexity($content),
        ];
    }

    /**
     * Calculate readability score
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
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    // Personality analysis methods (simplified implementations)
    private function analyzeExtroversion(string $content): float { 
        $extroversionWords = ['party', 'social', 'friends', 'group', 'together', 'fun', 'excited'];
        $score = 0;
        foreach ($extroversionWords as $word) {
            $score += substr_count(strtolower($content), $word);
        }
        return min(1.0, $score / 10);
    }
    
    private function analyzeOpenness(string $content): float { 
        $opennessWords = ['creative', 'art', 'music', 'travel', 'new', 'different', 'explore'];
        $score = 0;
        foreach ($opennessWords as $word) {
            $score += substr_count(strtolower($content), $word);
        }
        return min(1.0, $score / 10);
    }
    
    private function analyzeConscientiousness(string $content): float { 
        $conscientiousnessWords = ['plan', 'organize', 'goal', 'work', 'study', 'responsible'];
        $score = 0;
        foreach ($conscientiousnessWords as $word) {
            $score += substr_count(strtolower($content), $word);
        }
        return min(1.0, $score / 10);
    }
    
    private function analyzeAgreeableness(string $content): float { 
        $agreeablenessWords = ['help', 'kind', 'nice', 'support', 'care', 'love', 'compassion'];
        $score = 0;
        foreach ($agreeablenessWords as $word) {
            $score += substr_count(strtolower($content), $word);
        }
        return min(1.0, $score / 10);
    }
    
    private function analyzeNeuroticism(string $content): float { 
        $neuroticismWords = ['worry', 'anxious', 'stress', 'nervous', 'upset', 'sad'];
        $score = 0;
        foreach ($neuroticismWords as $word) {
            $score += substr_count(strtolower($content), $word);
        }
        return min(1.0, $score / 10);
    }
    
    private function analyzeFormality(string $content): float { 
        $formalWords = ['please', 'thank you', 'sincerely', 'regards', 'respectfully'];
        $informalWords = ['hey', 'yo', 'lol', 'omg', 'btw', 'tbh'];
        
        $formalCount = 0;
        $informalCount = 0;
        
        foreach ($formalWords as $word) {
            $formalCount += substr_count(strtolower($content), $word);
        }
        
        foreach ($informalWords as $word) {
            $informalCount += substr_count(strtolower($content), $word);
        }
        
        $total = $formalCount + $informalCount;
        return $total > 0 ? $formalCount / $total : 0.5;
    }
    
    private function analyzeHumorUsage(string $content): float { 
        $humorIndicators = ['lol', 'haha', 'funny', 'joke', 'laugh', '😄', '😂', '🤣'];
        $score = 0;
        foreach ($humorIndicators as $indicator) {
            $score += substr_count(strtolower($content), $indicator);
        }
        return min(1.0, $score / 5);
    }
    
    private function analyzeEmojiUsage(string $content): float { 
        $emojiCount = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', $content);
        $wordCount = str_word_count($content);
        return $wordCount > 0 ? min(1.0, $emojiCount / $wordCount) : 0;
    }
    
    private function analyzeSentenceLength(string $content): float { 
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $sentenceCount = count(array_filter($sentences));
        
        if ($sentenceCount == 0) return 0.5;
        
        $avgWordsPerSentence = $words / $sentenceCount;
        return min(1.0, $avgWordsPerSentence / 20); // Normalize to 20 words per sentence
    }
    
    private function analyzeVocabularyComplexity(string $content): float { 
        $words = str_word_count($content, 1);
        $complexWords = 0;
        
        foreach ($words as $word) {
            if (strlen($word) > 6) { // Words longer than 6 characters
                $complexWords++;
            }
        }
        
        $totalWords = count($words);
        return $totalWords > 0 ? min(1.0, $complexWords / $totalWords) : 0;
    }
    
    private function calculateActivityLevel(User $user): float { 
        $messageCount = BulletinMessage::where('user_id', $user->id)->count();
        $daysSinceJoined = $user->created_at->diffInDays(now());
        
        return $daysSinceJoined > 0 ? min(1.0, $messageCount / $daysSinceJoined) : 0;
    }
    
    private function analyzeEngagementPatterns(User $user): array { 
        return [
            'peak_hours' => [],
            'content_types' => [],
            'interaction_style' => 'balanced',
        ];
    }
    
    private function getRecentBoardActivity(BulletinBoard $board): array { 
        $count = BulletinMessage::where('bulletin_board_id', $board->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        return [
            'messages_last_7_days' => $count,
        ];
    }
    
    private function getPopularBoardTopics(BulletinBoard $board): array { 
        return [];
    }
    
    private function getBoardUserDemographics(BulletinBoard $board): array { 
        return [];
    }
}
