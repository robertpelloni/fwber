<?php

namespace App\Services;

use App\Models\ProximityArtifact;
use App\Services\Ai\Llm\LlmManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocalPulseAnalyticsService
{
    public function __construct(
        private readonly LlmManager $llmManager
    ) {}

    /**
     * Get an aggregated "Vibe" analysis for a specific location.
     */
    public function getNeighborhoodVibe(float $lat, float $lng, int $radiusMeters = 2000): array
    {
        $cacheKey = "vibe_analysis:{$lat}:{$lng}:{$radiusMeters}";

        return Cache::remember($cacheKey, 600, function () use ($lat, $lng, $radiusMeters) {
            $recentPosts = ProximityArtifact::active()
                ->type('board_post')
                ->withinBox($lat, $lng, $radiusMeters)
                ->latest()
                ->limit(50)
                ->get();

            if ($recentPosts->isEmpty()) {
                return [
                    'vibe' => 'Quiet',
                    'sentiment' => 0.5,
                    'trending_keywords' => [],
                    'activity_score' => 0,
                    'summary' => 'Not enough data for this area yet.',
                ];
            }

            // Prepare text for AI analysis
            $corpus = $recentPosts->pluck('content')->join("\n---\n");

            try {
                $analysis = $this->analyzeCorpusWithAi($corpus);
                
                return array_merge($analysis, [
                    'activity_score' => $recentPosts->count() * 2, // Simple count-based score
                    'post_count' => $recentPosts->count(),
                    'last_updated' => now()->toISOString(),
                ]);
            } catch (\Throwable $e) {
                Log::error("Vibe analysis AI failure: " . $e->getMessage());
                return [
                    'vibe' => 'Active',
                    'sentiment' => 0.6,
                    'trending_keywords' => [],
                    'activity_score' => $recentPosts->count(),
                    'summary' => 'Local activity detected.',
                ];
            }
        });
    }

    private function analyzeCorpusWithAi(string $corpus): array
    {
        $prompt = "Analyze the following local social media posts from a specific neighborhood and return a JSON object with:
        1. 'vibe': A one-word description of the current atmosphere (e.g. Energetic, Chill, Frustrated, Festive).
        2. 'sentiment': A float from 0 (negative) to 1 (positive).
        3. 'trending_keywords': An array of the top 5 most frequent or impactful keywords.
        4. 'summary': A 2-sentence summary of what people are talking about.

        POSTS:
        {$corpus}
        
        Return ONLY the JSON object.";

        $response = $this->llmManager->driver('openai')->chat([
            ['role' => 'system', 'content' => 'You are an expert neighborhood sentiment analyst.'],
            ['role' => 'user', 'content' => $prompt]
        ]);

        return json_decode($response, true) ?? [];
    }
}
