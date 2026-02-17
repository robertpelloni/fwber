<?php

namespace App\Services\MediaAnalysis\Drivers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Likelihood;
use Illuminate\Support\Facades\Log;

class GoogleVisionDriver implements MediaAnalysisInterface
{
    protected $client;

    public function __construct($client = null)
    {
        if ($client) {
            $this->client = $client;
            return;
        }

        // Rely on GOOGLE_APPLICATION_CREDENTIALS env var or default auth strategies
        if (config('services.google.vision.enabled', false)) {
            $this->client = new ImageAnnotatorClient([
                'credentials' => config('services.google.vision.credentials_path')
            ]);
        }
    }

    public function analyze(string $url, string $type): MediaAnalysisResult
    {
        if (!$this->client) {
            Log::warning('Google Vision client not initialized.');
            return new MediaAnalysisResult(true, [], [], 0.0, ['error' => 'Google Vision not configured']);
        }

        try {
            $imageContent = file_get_contents($url); // Assuming local path or URL reachable
            $response = $this->client->annotateImage(
                $imageContent,
                [Type::SAFE_SEARCH_DETECTION, Type::LABEL_DETECTION]
            );

            // Safe Search
            $safeSearch = $response->getSafeSearchAnnotation();
            $isSafe = true;
            $warnings = [];
            
            if ($safeSearch) {
                $adult = $safeSearch->getAdult();
                $violence = $safeSearch->getViolence();
                $racy = $safeSearch->getRacy();

                if ($adult >= Likelihood::LIKELY || $violence >= Likelihood::LIKELY) {
                    $isSafe = false;
                    $warnings[] = 'Content flagged as unsafe (Adult/Violence).';
                }
                
                // Stricter check for 'Racy' if needed, or just warn
                if ($racy >= Likelihood::VERY_LIKELY) {
                    $warnings[] = 'Content flagged as potentially racy.';
                }
            }

            // Labels
            $labels = [];
            if ($response->getLabelAnnotations()) {
                foreach ($response->getLabelAnnotations() as $label) {
                    $labels[] = $label->getDescription();
                }
            }

            return new MediaAnalysisResult(
                $isSafe,
                $labels,
                $warnings,
                $isSafe ? 1.0 : 0.0,
                ['source' => 'google_vision']
            );

        } catch (\Exception $e) {
            Log::error('Google Vision Analysis failed: ' . $e->getMessage());
            return new MediaAnalysisResult(true, [], [], 0.0, ['error' => $e->getMessage()]); // Fail open or closed?
        } finally {
            if ($this->client) {
                $this->client->close();
            }
        }
    }

    public function compareFaces(string $sourcePath, string $targetPath): float
    {
        // Google Vision API (standard) does not support Face Comparison (Verification) out of the box
        // We return 0.0 or log warning
        Log::warning('GoogleVisionDriver: compareFaces not supported in standard API.');
        return 0.0;
    }
}
