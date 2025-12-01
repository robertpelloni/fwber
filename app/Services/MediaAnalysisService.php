<?php

namespace App\Services;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Illuminate\Support\Facades\Log;

class MediaAnalysisService implements MediaAnalysisInterface
{
    /**
     * Analyze media for safety and content.
     *
     * @param string $url
     * @param string $type 'image', 'video', 'audio'
     * @return MediaAnalysisResult
     */
    public function analyze(string $url, string $type): MediaAnalysisResult
    {
        Log::info("Analyzing media: {$url} ({$type})");

        // Mock implementation
        // In a real implementation, this would call AWS Rekognition, Google Vision, etc.
        
        // 1. Check for explicit "unsafe" trigger in filename
        if (str_contains(strtolower($url), 'unsafe') || str_contains(strtolower($url), 'explicit')) {
            return new MediaAnalysisResult(
                safe: false,
                labels: ['Explicit', 'Violence', 'Restricted'],
                moderationLabels: ['Explicit Content', 'Violence'],
                confidence: 0.95,
                metadata: ['reason' => 'Mock detection triggered by filename keyword']
            );
        }

        // 2. Deterministic Simulation based on filename hash
        // This ensures the same file always gets the same "random" labels
        $hash = crc32($url);
        $scenarios = [
            0 => ['labels' => ['Person', 'Smile', 'Portrait', 'Indoors'], 'mod' => []],
            1 => ['labels' => ['Nature', 'Landscape', 'Trees', 'Sky'], 'mod' => []],
            2 => ['labels' => ['Food', 'Drink', 'Restaurant', 'Table'], 'mod' => []],
            3 => ['labels' => ['Cat', 'Pet', 'Animal', 'Cute'], 'mod' => []],
            4 => ['labels' => ['City', 'Building', 'Architecture', 'Street'], 'mod' => []],
            5 => ['labels' => ['Beach', 'Ocean', 'Sand', 'Summer'], 'mod' => ['Swimwear']], // Borderline
        ];
        
        $scenarioIndex = $hash % count($scenarios);
        $scenario = $scenarios[$scenarioIndex];
        
        // Simulate confidence variation
        $confidence = 0.85 + (($hash % 15) / 100); // 0.85 to 0.99

        return new MediaAnalysisResult(
            safe: true,
            labels: $scenario['labels'],
            moderationLabels: $scenario['mod'],
            confidence: $confidence,
            metadata: [
                'width' => 1024, 
                'height' => 768,
                'mock' => true,
                'scenario_id' => $scenarioIndex
            ]
        );
    }
}
