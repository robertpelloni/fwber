<?php

namespace App\Services\MediaAnalysis;

interface MediaAnalysisInterface
{
    /**
     * Analyze media for safety and content.
     *
     * @param string $url
     * @param string $type 'image', 'video', 'audio'
     * @return MediaAnalysisResult
     */
    public function analyze(string $url, string $type): MediaAnalysisResult;
}
