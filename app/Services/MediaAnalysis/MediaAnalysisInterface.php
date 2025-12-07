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

    /**
     * Compare faces in two images.
     *
     * @param string $sourcePath Path to the source image (e.g., selfie)
     * @param string $targetPath Path to the target image (e.g., profile photo)
     * @return float Similarity score (0-100)
     */
    public function compareFaces(string $sourcePath, string $targetPath): float;
}
