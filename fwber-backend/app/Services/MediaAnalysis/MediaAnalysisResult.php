<?php

namespace App\Services\MediaAnalysis;

class MediaAnalysisResult
{
    public bool $safe;
    public array $labels;
    public array $moderationLabels;
    public float $confidence;
    public array $metadata;

    public function __construct(
        bool $safe,
        array $labels = [],
        array $moderationLabels = [],
        float $confidence = 0.0,
        array $metadata = []
    ) {
        $this->safe = $safe;
        $this->labels = $labels;
        $this->moderationLabels = $moderationLabels;
        $this->confidence = $confidence;
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'safe' => $this->safe,
            'labels' => $this->labels,
            'moderation_labels' => $this->moderationLabels,
            'confidence' => $this->confidence,
            'metadata' => $this->metadata,
        ];
    }
}
