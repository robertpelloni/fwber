<?php

namespace App\Services\MediaAnalysis\Drivers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class AwsRekognitionDriver implements MediaAnalysisInterface
{
    protected ?RekognitionClient $client = null;

    public function __construct()
    {
        $key = config('services.aws.key');
        $secret = config('services.aws.secret');
        $region = config('services.aws.region');

        if ($key && $secret && $region) {
            $this->client = new RekognitionClient([
                'region' => $region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ]
            ]);
        }
    }

    public function analyze(string $url, string $type): MediaAnalysisResult
    {
        if (!$this->client) {
            Log::warning('AWS Rekognition credentials not configured. Falling back to safe result.');
            return new MediaAnalysisResult(
                true,
                ['aws_not_configured'],
                [],
                0.0,
                ['source' => 'aws_rekognition_missing_creds']
            );
        }

        try {
            // Get image bytes
            // Assuming $url is the path relative to public disk as used in PhotoController
            if (!Storage::disk('public')->exists($url)) {
                throw new Exception("File not found: $url");
            }
            
            $imageBytes = Storage::disk('public')->get($url);

            // 1. Detect Moderation Labels (Safety)
            $moderationResult = $this->client->detectModerationLabels([
                'Image' => ['Bytes' => $imageBytes],
                'MinConfidence' => 60
            ]);
            
            $moderationLabels = $moderationResult['ModerationLabels'] ?? [];
            $unsafeLabels = [];
            
            foreach ($moderationLabels as $label) {
                $unsafeLabels[] = $label['Name'] . ' (' . round($label['Confidence']) . '%)';
            }
            
            $isSafe = empty($unsafeLabels);

            // 2. Detect Content Labels (Tags)
            $contentLabels = [];
            if ($isSafe) {
                $labelResult = $this->client->detectLabels([
                    'Image' => ['Bytes' => $imageBytes],
                    'MaxLabels' => 15,
                    'MinConfidence' => 70
                ]);
                
                foreach ($labelResult['Labels'] as $label) {
                    $contentLabels[] = $label['Name'];
                }
            }

            return new MediaAnalysisResult(
                $isSafe,
                $contentLabels,
                $unsafeLabels,
                $isSafe ? 1.0 : 0.0,
                ['source' => 'aws_rekognition']
            );

        } catch (Exception $e) {
            Log::error('AWS Rekognition Analysis Failed', [
                'error' => $e->getMessage(),
                'file' => $url
            ]);
            
            // Rethrow so the upload fails rather than allowing potentially unsafe content silently
            throw $e;
        }
    }
}
