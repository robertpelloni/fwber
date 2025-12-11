<?php

namespace App\Services;

use App\Models\User;
use App\Services\Ai\Llm\LlmManager;
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
            $conversation .= "{$sender}: {$msg['content']}\n";
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
}
