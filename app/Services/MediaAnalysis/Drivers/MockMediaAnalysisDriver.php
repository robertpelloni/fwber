<?php

namespace App\Services\MediaAnalysis\Drivers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Illuminate\Support\Facades\Log;

class MockMediaAnalysisDriver implements MediaAnalysisInterface
{
    public function analyze(string $url, string $type): MediaAnalysisResult
    {
        Log::info("MockMediaAnalysisDriver: Analyzing media: {$url} ({$type})");

        // Simulate analysis based on type
        return match ($type) {
            'image' => $this->analyzeImage($url),
            'video' => $this->analyzeVideo($url),
            'audio' => $this->analyzeAudio($url),
            default => new MediaAnalysisResult(false, [], ['error' => 'Unsupported media type']),
        };
    }

    protected function analyzeImage(string $url): MediaAnalysisResult
    {
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
                'scenario_id' => $scenarioIndex,
                'source' => 'mock_driver'
            ]
        );
    }

    protected function analyzeVideo(string $url): MediaAnalysisResult
    {
        return new MediaAnalysisResult(
            true,
            ['movement', 'talking'],
            [],
            0.95,
            ['duration' => 15, 'source' => 'mock_driver']
        );
    }

    protected function analyzeAudio(string $url): MediaAnalysisResult
    {
        return new MediaAnalysisResult(
            true,
            [],
            [],
            0.92,
            ['transcription' => 'Hello, this is a test message.', 'sentiment' => 'positive', 'source' => 'mock_driver']
        );
    }

    public function compareFaces(string $sourcePath, string $targetPath): float
    {
        Log::info("MockMediaAnalysisDriver: Comparing faces: {$sourcePath} vs {$targetPath}");

        // If filename contains "mismatch", return low score
        if (str_contains(strtolower($sourcePath), 'mismatch') || str_contains(strtolower($targetPath), 'mismatch')) {
            return 15.5;
        }

        // Otherwise return high score
        return 95.5;
    }
}
