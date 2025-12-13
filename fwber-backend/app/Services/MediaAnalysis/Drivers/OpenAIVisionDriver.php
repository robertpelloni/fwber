<?php

namespace App\Services\MediaAnalysis\Drivers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class OpenAIVisionDriver implements MediaAnalysisInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    public function analyze(string $url, string $type): MediaAnalysisResult
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured. Falling back to safe result.');
            return new MediaAnalysisResult(
                true,
                ['openai_not_configured'],
                [],
                0.0,
                ['source' => 'openai_vision_missing_creds']
            );
        }

        try {
            // Get image bytes and encode to base64
            if (!Storage::disk('public')->exists($url)) {
                throw new Exception("File not found: $url");
            }
            
            $imageBytes = Storage::disk('public')->get($url);
            $base64Image = base64_encode($imageBytes);
            $mimeType = Storage::disk('public')->mimeType($url) ?? 'image/jpeg';

            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a content moderation AI. Analyze the image for safety. Return JSON with: "safe" (boolean), "labels" (array of strings describing content), "unsafe_reasons" (array of strings if unsafe), "confidence" (float 0-1).'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analyze this image for moderation purposes.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:$mimeType;base64,$base64Image"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 300
                ]);

            if (!$response->successful()) {
                throw new Exception('OpenAI API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = json_decode($data['choices'][0]['message']['content'], true);

            return new MediaAnalysisResult(
                $content['safe'] ?? false,
                $content['labels'] ?? [],
                $content['unsafe_reasons'] ?? [],
                $content['confidence'] ?? 0.0,
                ['source' => 'openai_vision']
            );

        } catch (Exception $e) {
            Log::error('OpenAI Vision Analysis Failed', [
                'error' => $e->getMessage(),
                'file' => $url
            ]);
            
            // Fail open or closed? Usually closed for safety.
            throw $e;
        }
    }

    public function compareFaces(string $sourcePath, string $targetPath): float
    {
        // OpenAI Vision isn't optimized for face comparison/verification in the same way Rekognition is.
        // We can try to ask it, but it's not a dedicated face matching API.
        // For now, we'll return a mock value or implement a basic prompt.
        
        if (empty($this->apiKey)) {
            return 99.9;
        }

        try {
            $sourceBytes = base64_encode(Storage::disk('public')->get($sourcePath));
            $targetBytes = base64_encode(Storage::disk('public')->get($targetPath));
            $mimeType = 'image/jpeg'; // Simplified

            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a face verification AI. Compare the two images. Do they depict the same person? Return JSON with: "match" (boolean), "similarity" (float 0-100).'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => "data:$mimeType;base64,$sourceBytes"]
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => "data:$mimeType;base64,$targetBytes"]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 100
                ]);

            $data = $response->json();
            $content = json_decode($data['choices'][0]['message']['content'], true);

            return (float) ($content['similarity'] ?? 0.0);

        } catch (Exception $e) {
            Log::error('OpenAI Face Compare Failed', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }
}
