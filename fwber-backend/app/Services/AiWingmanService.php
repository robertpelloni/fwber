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
     * Generate a humorous "roast" of the user's profile.
     *
     * @param User $user
     * @return string
     */
    public function roastProfile(User $user): string
    {
        $profile = $user->profile;
        $updatedAt = $profile ? $profile->updated_at->timestamp : 0;
        $cacheKey = "wingman:roast:{$user->id}:{$updatedAt}";

        return Cache::remember($cacheKey, 86400, function () use ($user, $profile) {
            $prompt = $this->buildRoastPrompt($user, $profile);

            try {
                $response = $this->llmManager->driver()->chat([
                    ['role' => 'system', 'content' => 'You are a professional comedian and roast master. Your goal is to humorously critique dating profiles. Be savage but keep it fun and safe (no hate speech).'],
                    ['role' => 'user', 'content' => $prompt]
                ], ['temperature' => 0.9]);

                return $response->content;
            } catch (\Exception $e) {
                Log::error("AiWingmanService: Failed to roast profile: " . $e->getMessage());
                return "Your profile is so perfect I can't even find anything to roast! (Or maybe my servers are just busy...)";
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

    protected function buildRoastPrompt(User $user, $profile): string
    {
        $bio = $profile->bio ?? 'No bio provided.';
        $interests = implode(', ', $profile->interests ?? []);
        $job = $profile->occupation ?? 'Unemployed';
        $age = $profile->age ?? 'Unknown age';
        
        return <<<EOT
Roast this dating profile. Be funny, witty, and slightly mean, but don't cross the line into harassment.

Profile Details:
- Age: {$age}
- Job: {$job}
- Bio: "{$bio}"
- Interests: {$interests}

Make fun of their clichés, their job, or their lack of effort. Keep it under 280 characters so it's tweetable.
EOT;
    }
}
