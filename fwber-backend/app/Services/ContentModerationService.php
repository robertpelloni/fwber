<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContentModerationService
{
    /**
     * Moderate content using regex and keyword matching as a fallback
     * when AI providers are unavailable or for quick synchronous checks.
     */
    public function moderateContent(string $content): array
    {
        // Simple heuristic fallback if AI fails or isn't configured
        $flagged = false;
        $categories = [];

        $patterns = [
            'hate' => '/\b(hate|kill|die|racist_slur)\b/i',
            'sexual' => '/\b(sex|porn|nude|xxx)\b/i',
            'spam' => '/\b(buy|crypto|invest|free money)\b/i',
            'violence' => '/\b(murder|attack|bomb|weapon)\b/i',
        ];

        foreach ($patterns as $category => $pattern) {
            if (preg_match($pattern, $content)) {
                $flagged = true;
                $categories[$category] = 1.0; // High confidence for explicit keyword match
            }
        }

        return [
            'flagged' => $flagged,
            'categories' => $categories,
            'confidence' => $flagged ? 1.0 : 0.0,
            'provider' => 'local_fallback',
            'action' => $flagged ? 'reject' : 'approve',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
