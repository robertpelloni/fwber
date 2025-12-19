<?php

namespace App\Services;

use App\Models\User;
use App\Models\Venue;
use App\Services\Ai\Llm\LlmManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiWingmanService
{
    protected LlmManager $llmManager;

    public function __construct(LlmManager $llmManager)
    {
        $this->llmManager = $llmManager;
    }

    /**
     * Generate ice breaker suggestions for a match.
     *
     * @param User $user The user asking for suggestions
     * @param User $match The matched user
     * @return array List of suggested ice breakers
     */
    public function generateIceBreakers(User $user, User $match): array
    {
        $cacheKey = "wingman:icebreakers:{$user->id}:{$match->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $match) {
            $userProfile = $user->profile;
            $matchProfile = $match->profile;

            if (!$userProfile || !$matchProfile) {
                return ["Hi! How's it going?", "Hey! I saw we matched."];
            }

            $prompt = $this->buildIceBreakerPrompt($user, $match);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a helpful dating coach and wingman. Your goal is to help users start conversations that are engaging, respectful, and relevant to their shared interests.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.7]);

                return $this->parseSuggestions($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to generate ice breakers: " . $e->getMessage());
                return ["Hi! How's it going?", "Hey! I saw we matched."];
            }
        });
    }

    /**
     * Generate reply suggestions based on conversation history.
     *
     * @param User $user The user asking for suggestions
     * @param User $match The matched user
     * @param array $history Array of message objects/arrays ['sender_id' => ..., 'content' => ...]
     * @return array List of suggested replies
     */
    public function generateReplySuggestions(User $user, User $match, array $history): array
    {
        $prompt = $this->buildReplyPrompt($user, $match, $history);

        try {
            $response = $this->llmManager->driver()->chat([
                ['role' => 'system', 'content' => 'You are a helpful dating coach and wingman. Your goal is to help users keep conversations going. Suggestions should be casual, friendly, and encourage a response.'],
                ['role' => 'user', 'content' => $prompt]
            ], ['temperature' => 0.7]);

            return $this->parseSuggestions($response->content);
        } catch (\Exception $e) {
            Log::error("AiWingmanService: Failed to generate replies: " . $e->getMessage());
            return ["That sounds interesting!", "Tell me more about that."];
        }
    }

    /**
     * Analyze a user's profile and provide feedback.
     *
     * @param User $user
     * @return array Structured feedback
     */
    public function analyzeProfile(User $user): array
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:profile_analysis:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $photos = $user->photos;
            
            $prompt = $this->buildProfileAnalysisPrompt($user, $profile, $photos);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are an expert dating profile consultant. Your goal is to help users optimize their profiles to attract better matches. Be honest but constructive.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.7]);

                return $this->parseAnalysis($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to analyze profile: " . $e->getMessage());
                return [
                    'score' => 0,
                    'strengths' => [],
                    'weaknesses' => ['Service unavailable'],
                    'tips' => ['Please try again later.']
                ];
            }
        });
    }

    /**
     * Suggest date ideas based on user profiles and location.
     *
     * @param User $user
     * @param User $match
     * @param string|null $location
     * @return array List of date ideas
     */
    public function suggestDateIdeas(User $user, User $match, ?string $location = null): array
    {
        $locKey = $location ? md5($location) : 'none';
        $cacheKey = "wingman:date_ideas:{$user->id}:{$match->id}:{$locKey}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $match, $location) {
            // Fetch nearby venues if location is provided (simplified for now)
            // In a real implementation, we would use geospatial search
            $venues = [];
            if ($location) {
                // Placeholder: Fetch top 5 active venues
                $venues = Venue::where('is_active', true)->limit(5)->get();
            }

            $prompt = $this->buildDateIdeasPrompt($user, $match, $venues, $location);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a creative dating concierge. Your goal is to suggest unique and personalized date ideas based on the interests of two people.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.8]);

                return $this->parseDateIdeas($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to suggest date ideas: " . $e->getMessage());
                return [
                    [
                        'title' => 'Coffee Date',
                        'description' => 'Grab a coffee and get to know each other.',
                        'reason' => 'Classic and low pressure.'
                    ]
                ];
            }
        });
    }

    /**
     * Generate a personalized explanation of why two users are a good match.
     *
     * @param User $user
     * @param User $match
     * @return string
     */
    public function generateMatchExplanation(User $user, User $match): string
    {
        $cacheKey = "wingman:match_explanation:{$user->id}:{$match->id}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $match) {
            $prompt = $this->buildMatchExplanationPrompt($user, $match);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are an insightful relationship expert. Your goal is to explain to a user why they matched with someone else, highlighting shared interests and complementary traits.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.7]);

                return $response->content;
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to generate match explanation: " . $e->getMessage());
                return "You both have shared interests and compatible preferences!";
            }
        });
    }

    protected function buildIceBreakerPrompt(User $user, User $match): string
    {
        $userInterests = implode(', ', $user->profile->interests ?? []);
        $matchInterests = implode(', ', $match->profile->interests ?? []);
        $matchBio = $match->profile->bio ?? 'No bio';

        return <<<EOT
Generate 3 distinct ice breaker messages for me to send to my match.
My interests: {$userInterests}
Match's interests: {$matchInterests}
Match's bio: {$matchBio}

The messages should be:
1. Friendly and casual.
2. Reference a shared interest if possible, or something from their bio.
3. A question to invite a response.

Format the output as a JSON array of strings.
EOT;
    }

    protected function buildReplyPrompt(User $user, User $match, array $history): string
    {
        $conversation = "";
        foreach ($history as $msg) {
            $sender = ($msg['sender_id'] == $user->id) ? "Me" : "Match";
            
            $content = $msg['content'];
            if (!empty($msg['transcription'])) {
                $content = $msg['transcription'];
            } elseif (($msg['message_type'] ?? '') === 'audio') {
                $content = "[Audio Message]";
            }

            $conversation .= "{$sender}: {$content}\n";
        }

        return <<<EOT
Here is the conversation history so far:
{$conversation}

Generate 3 suggested replies for me to send next.
The replies should be:
1. Relevant to the last message.
2. Keep the conversation flowing.
3. Varied in tone (e.g., one funny, one curious, one direct).

Format the output as a JSON array of strings.
EOT;
    }

    protected function buildProfileAnalysisPrompt(User $user, $profile, $photos): string
    {
        $bio = $profile->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile->interests ?? []);
        $photoCount = $photos->count();
        $hasVerified = $profile->is_verified ? 'Yes' : 'No';
        
        // Basic stats
        $stats = "Photos: {$photoCount}, Verified: {$hasVerified}";

        return <<<EOT
Analyze the following dating profile:

Bio: "{$bio}"
Interests: {$interests}
Stats: {$stats}

Provide a structured analysis in JSON format with the following keys:
- score: (integer 0-100)
- strengths: (array of strings) What is good about the profile?
- weaknesses: (array of strings) What is missing or could be improved?
- tips: (array of strings) Actionable advice to improve the profile.

Focus on:
1. Bio quality (is it engaging, specific, too short/long?)
2. Interest variety.
3. Completeness (photos, verification).
EOT;
    }

    protected function buildDateIdeasPrompt(User $user, User $match, $venues, ?string $location): string
    {
        $userInterests = implode(', ', $user->profile->interests ?? []);
        $matchInterests = implode(', ', $match->profile->interests ?? []);
        
        $venueContext = "";
        if (count($venues) > 0) {
            $venueContext = "Here are some local venues you can recommend if they fit:\n";
            foreach ($venues as $venue) {
                $venueContext .= "- {$venue->name} ({$venue->business_type}): {$venue->description}\n";
            }
        }

        return <<<EOT
Suggest 3 date ideas for two people with the following interests:

Person A: {$userInterests}
Person B: {$matchInterests}

Location Context: {$location}
{$venueContext}

Provide the output as a JSON array of objects with keys:
- title: (string) A catchy title for the date.
- description: (string) A short description of the activity.
- reason: (string) Why this is a good match for their shared (or complementary) interests.

Ensure the ideas are distinct (e.g., one active, one relaxing, one cultural).
EOT;
    }

    protected function parseSuggestions(string $content): array
    {
        // Attempt to parse JSON from the response
        // LLMs might wrap JSON in markdown code blocks
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: split by newlines if not JSON
        $lines = explode("\n", $content);
        $suggestions = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        // Clean up numbering (e.g., "1. ")
        return array_map(function($line) {
            return preg_replace('/^\d+\.\s*/', '', trim($line));
        }, array_values($suggestions));
    }

    protected function parseAnalysis(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback structure if JSON parsing fails
        return [
            'score' => 50,
            'strengths' => ['Could not parse analysis.'],
            'weaknesses' => [],
            'tips' => ['Please try again.']
        ];
    }

    protected function parseDateIdeas(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [
            [
                'title' => 'Coffee & Walk',
                'description' => 'Grab a coffee and take a walk in a nearby park.',
                'reason' => 'Simple, low pressure, and allows for conversation.'
            ]
        ];
    }

    /**
     * Analyze a draft message and provide feedback.
     *
     * @param User $user
     * @param User $match
     * @param string $draft
     * @param array $history
     * @return array
     */
    public function analyzeDraftMessage(User $user, User $match, string $draft, array $history): array
    {
        $prompt = $this->buildDraftAnalysisPrompt($user, $match, $draft, $history);

        try {
            $response = $this->llmManager->driver()->chat([
                ['role' => 'system', 'content' => 'You are a communication coach. Your goal is to help users send better messages in a dating context. Analyze the tone, clarity, and potential impact of the draft message.'],
                ['role' => 'user', 'content' => $prompt]
            ], ['temperature' => 0.7]);

            return $this->parseDraftAnalysis($response->content);
        } catch (\Exception $e) {
            Log::error("AiWingmanService: Failed to analyze draft: " . $e->getMessage());
            return [
                'score' => 50,
                'tone' => 'Neutral',
                'feedback' => 'Unable to analyze message at this time.',
                'suggestion' => null
            ];
        }
    }

    protected function buildDraftAnalysisPrompt(User $user, User $match, string $draft, array $history): string
    {
        $conversation = "";
        // Take last 5 messages for context
        $recentHistory = array_slice($history, -5);
        foreach ($recentHistory as $msg) {
            $sender = ($msg['sender_id'] == $user->id) ? "Me" : "Match";
            $content = $msg['content'];
            if (!empty($msg['transcription'])) {
                $content = $msg['transcription'];
            } elseif (($msg['message_type'] ?? '') === 'audio') {
                $content = "[Audio Message]";
            }
            $conversation .= "{$sender}: {$content}\n";
        }

        return <<<EOT
Context (Last 5 messages):
{$conversation}

Draft Message (Me): "{$draft}"

Analyze this draft message.
1. Rate it from 0-100 based on effectiveness, politeness, and engagement.
2. Identify the tone (e.g., Flirty, Aggressive, Passive, Funny, Neutral).
3. Provide 1 sentence of feedback.
4. If the score is below 80, provide a rewritten suggestion. If it's good, suggestion can be null.

Output JSON:
{
    "score": 85,
    "tone": "Friendly",
    "feedback": "Great opener, very engaging.",
    "suggestion": null
}
EOT;
    }

    protected function parseDraftAnalysis(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'score' => $decoded['score'] ?? 50,
                'tone' => $decoded['tone'] ?? 'Unknown',
                'feedback' => $decoded['feedback'] ?? 'No feedback provided.',
                'suggestion' => $decoded['suggestion'] ?? null
            ];
        }

        return [
            'score' => 50,
            'tone' => 'Unknown',
            'feedback' => 'Could not parse analysis.',
            'suggestion' => null
        ];
    }

    /**
     * Generate a humorous "roast" or enthusiastic "hype" of the user's profile.
     *
     * @param User $user
     * @param string $mode 'roast' or 'hype'
     * @return string
     */
    public function roastProfile(User $user, string $mode = 'roast'): string
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:{$mode}:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile, $mode) {
            $prompt = $this->buildRoastPrompt($user, $profile, $mode);

            $systemPrompt = $mode === 'hype'
                ? 'You are an enthusiastic hype man. Your goal is to make this person sound like the biggest catch in the world. Use slang, emojis, and high energy.'
                : 'You are a professional comedian and roast master. Your goal is to humorously critique dating profiles. Be savage but keep it fun and safe (no hate speech).';

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.9]);

                return $response->content;
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to generate {$mode}: " . $e->getMessage());
                return $mode === 'hype'
                    ? "You're amazing! (My hype circuits are overloaded right now, try again later!)"
                    : "Your profile is so perfect I can't even find anything to roast! (Or maybe my servers are just busy...)";
            }
        });
    }

    /**
     * Generate "Red Flags" and "Green Flags" for a user's profile.
     *
     * @param User $user
     * @return array
     */
    public function checkVibe(User $user): array
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:vibe_check:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $prompt = $this->buildVibeCheckPrompt($user, $profile);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a "Vibe Checker". Your job is to analyze dating profiles and identify "Green Flags" (positive traits) and "Red Flags" (potential warning signs or humorous observations). Be witty, observant, and keep it lighthearted.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.8]);

                return $this->parseVibeCheck($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to check vibe: " . $e->getMessage());
                return [
                    'green_flags' => ['Mystery', 'Unpredictable'],
                    'red_flags' => ['Service unavailable', 'Too hot to handle']
                ];
            }
        });
    }

    /**
     * Generate a humorous "Dating Fortune" for the user.
     *
     * @param User $user
     * @return string
     */
    public function predictFortune(User $user): string
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:fortune:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $prompt = $this->buildFortunePrompt($user, $profile);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a mystical "Dating Oracle". Your job is to predict the user\'s romantic future in a humorous, fortune-cookie style. Be specific, slightly absurd, but encouraging.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.9]);

                return $response->content;
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to predict fortune: " . $e->getMessage());
                return "In the near future, you will encounter a server error that leads to a beautiful connection with customer support.";
            }
        });
    }

    /**
     * Generate a "Cosmic Match" prediction for the user.
     *
     * @param User $user
     * @return array
     */
    public function predictCosmicMatch(User $user): array
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:cosmic_match:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $prompt = $this->buildCosmicMatchPrompt($user, $profile);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are an expert astrologer and dating coach. Your job is to analyze profiles and determine the best and worst zodiac matches based on personality traits inferred from the bio and interests.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.8]);

                return $this->parseCosmicMatch($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to predict cosmic match: " . $e->getMessage());
                return [
                    'best_match' => 'Unknown',
                    'best_reason' => 'The stars are cloudy today.',
                    'worst_match' => 'Unknown',
                    'worst_reason' => 'Try again later.'
                ];
            }
        });
    }

    protected function buildMatchExplanationPrompt(User $user, User $match): string
    {
        $userProfile = $user->profile;
        $matchProfile = $match->profile;

        $userInterests = implode(', ', $userProfile->interests ?? []);
        $matchInterests = implode(', ', $matchProfile->interests ?? []);
        
        $userBio = $userProfile->bio ?? '';
        $matchBio = $matchProfile->bio ?? '';

        return <<<EOT
Explain why these two people are a good match based on their profiles:

User (Me):
- Bio: "{$userBio}"
- Interests: {$userInterests}
- Age: {$userProfile->age}

Match (Them):
- Bio: "{$matchBio}"
- Interests: {$matchInterests}
- Age: {$matchProfile->age}

Write a short, encouraging paragraph (2-3 sentences) explaining the compatibility. Address "Me" directly.
Focus on shared interests, complementary vibes from the bio, or lifestyle compatibility.
EOT;
    }

    protected function buildRoastPrompt(User $user, $profile, string $mode = 'roast'): string
    {
        $bio = $profile?->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile?->interests ?? []);
        $job = $profile?->occupation ?? 'Unemployed';
        $age = $profile?->age ?? 'Unknown age';
        
        $instruction = $mode === 'hype'
            ? "Write a hype intro for this person. Make them sound irresistible. Focus on their best qualities and spin their flaws into features."
            : "Roast this dating profile. Be funny, witty, and slightly mean, but don't cross the line into harassment. Make fun of their clichés.";

        return <<<EOT
{$instruction}

Profile Details:
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}

Keep it under 280 characters so it's tweetable.
EOT;
    }

    protected function buildVibeCheckPrompt(User $user, $profile): string
    {
        $bio = $profile?->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile?->interests ?? []);
        $job = $profile?->occupation ?? 'Unknown';
        $age = $profile?->age ?? 'Unknown';

        return <<<EOT
Analyze this dating profile and list "Green Flags" and "Red Flags".

Profile:
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}

Output a JSON object with exactly these keys:
- green_flags: (array of 3-5 strings) Positive, attractive traits.
- red_flags: (array of 3-5 strings) Humorous warning signs or playful critiques.

Keep the flags short (2-5 words each).
EOT;
    }

    protected function buildFortunePrompt(User $user, $profile): string
    {
        $bio = $profile?->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile?->interests ?? []);
        $job = $profile?->occupation ?? 'Unknown';
        $age = $profile?->age ?? 'Unknown';

        return <<<EOT
Predict the romantic future for this person based on their profile.

Profile:
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}

Write a short, humorous "fortune cookie" style prediction (1-2 sentences).
It should be slightly mystical but grounded in their profile details if possible.
EOT;
    }

    protected function buildCosmicMatchPrompt(User $user, $profile): string
    {
        $bio = $profile?->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile?->interests ?? []);
        $job = $profile?->occupation ?? 'Unknown';
        $age = $profile?->age ?? 'Unknown';
        $zodiac = $profile?->zodiac_sign ?? 'Unknown';

        return <<<EOT
Analyze this dating profile and determine their "Cosmic Match" (Best Zodiac Sign) and "Cosmic Clash" (Worst Zodiac Sign).

Profile:
- Zodiac Sign: {$zodiac}
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}

Output a JSON object with exactly these keys:
- best_match: (string) The Zodiac sign that is their soulmate.
- best_reason: (string) A short, witty explanation why (1-2 sentences).
- worst_match: (string) The Zodiac sign they should avoid.
- worst_reason: (string) A short, witty explanation why (1-2 sentences).
EOT;
    }

    protected function parseCosmicMatch(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'best_match' => $decoded['best_match'] ?? 'Leo',
                'best_reason' => $decoded['best_reason'] ?? 'Because you need some drama.',
                'worst_match' => $decoded['worst_match'] ?? 'Scorpio',
                'worst_reason' => $decoded['worst_reason'] ?? 'Too intense for you.'
            ];
        }

        return [
            'best_match' => 'Libra',
            'best_reason' => 'Balance is key.',
            'worst_match' => 'Gemini',
            'worst_reason' => 'Two faces are too many.'
        ];
    }

    protected function parseVibeCheck(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'green_flags' => $decoded['green_flags'] ?? [],
                'red_flags' => $decoded['red_flags'] ?? []
            ];
        }

        return [
            'green_flags' => ['Good vibes only'],
            'red_flags' => ['AI confusion']
        ];
    }

    /**
     * Generate a "Scientific Nemesis" profile for the user.
     *
     * @param User $user
     * @return array
     */
    public function findNemesis(User $user): array
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:nemesis:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $prompt = $this->buildNemesisPrompt($user, $profile);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a "Scientific Matchmaker" gone rogue. Your job is to use psychological principles (Big Five, Myers-Briggs, etc.) to identify the absolute worst possible romantic match for a user. Be specific, analytical, but humorous.'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.8]);

                return $this->parseNemesis($response->content);
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to find nemesis: " . $e->getMessage());
                return [
                    'nemesis_type' => 'The Void',
                    'clashing_traits' => ['Everything'],
                    'why_it_would_fail' => 'Matter and anti-matter do not mix.',
                    'scientific_explanation' => 'Total protonic reversal.'
                ];
            }
        });
    }

    protected function buildNemesisPrompt(User $user, $profile): string
    {
        $bio = $profile?->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile?->interests ?? []);
        $job = $profile?->occupation ?? 'Unknown';
        $age = $profile?->age ?? 'Unknown';
        $mbti = $profile?->mbti ?? 'Unknown'; // Assuming we might have this, or just infer it

        return <<<EOT
Analyze this dating profile and construct their "Scientific Nemesis" (The person they are statistically least likely to succeed with).

Profile:
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}
- MBTI/Personality: {$mbti}

Output a JSON object with exactly these keys:
- nemesis_type: (string) A creative name for this archetype (e.g., "The Chaos Agent", "The Rigid Bureaucrat").
- clashing_traits: (array of 3 strings) Specific traits that would drive the user crazy.
- why_it_would_fail: (string) A scenario describing a disastrous date or interaction (1-2 sentences).
- scientific_explanation: (string) A pseudo-scientific explanation referencing personality theory (e.g., "High Openness vs. Extreme Conscientiousness clash").

Keep it fun but sound "smart".
EOT;
    }

    protected function parseNemesis(string $content): array
    {
        $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return [
                'nemesis_type' => $decoded['nemesis_type'] ?? 'The Unknown',
                'clashing_traits' => $decoded['clashing_traits'] ?? ['Mystery', 'Silence', 'Void'],
                'why_it_would_fail' => $decoded['why_it_would_fail'] ?? 'You would simply cease to exist.',
                'scientific_explanation' => $decoded['scientific_explanation'] ?? 'Data insufficient for meaningful analysis.'
            ];
        }

        return [
            'nemesis_type' => 'The Mirror',
            'clashing_traits' => ['Narcissism', 'Ego', 'Volume'],
            'why_it_would_fail' => 'You would fight over who gets to talk first.',
            'scientific_explanation' => 'Identical magnetic poles repel.'
        ];
    }
}
