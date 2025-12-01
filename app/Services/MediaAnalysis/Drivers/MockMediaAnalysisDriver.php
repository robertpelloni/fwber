<?php

namespace App\Services\MediaAnalysis\Drivers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;

class MockMediaAnalysisDriver implements MediaAnalysisInterface
{
    public function analyze(string $url, string $type): MediaAnalysisResult
    {
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
        return new MediaAnalysisResult(
            true,
            ['person', 'outdoor', 'smile'],
            [],
            0.98,
            ['source' => 'mock']
        );
    }

    protected function analyzeVideo(string $url): MediaAnalysisResult
    {
        return new MediaAnalysisResult(
            true,
            ['movement', 'talking'],
            [],
            0.95,
            ['duration' => 15, 'source' => 'mock']
        );
    }

    protected function analyzeAudio(string $url): MediaAnalysisResult
    {
        return new MediaAnalysisResult(
            true,
            [],
            [],
            0.92,
            ['transcription' => 'Hello, this is a test message.', 'sentiment' => 'positive', 'source' => 'mock']
        );
    }
}
