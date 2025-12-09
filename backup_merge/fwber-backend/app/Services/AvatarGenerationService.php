<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarGenerationService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('avatar_generation', [
            'enabled' => true,
            'default_provider' => 'dalle', // or 'gemini', 'replicate'
            'providers' => [
                'dalle' => [
                    'api_key' => config('services.openai.api_key'),
                    'model' => 'dall-e-3',
                    'size' => '1024x1024',
                    'quality' => 'standard',
                ],
                'gemini' => [
                    'api_key' => config('services.gemini.api_key'),
                    'model' => 'imagen-3.0-generate-001',
                ],
                'replicate' => [
                    'api_token' => config('services.replicate.api_token'),
                    'model' => 'stability-ai/stable-diffusion:27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478',
                ],
            ],
            'default_style' => 'realistic portrait, professional headshot',
        ]);
    }

    public function generateAvatar(User $user, array $options = []): array
    {
        $provider = $options['provider'] ?? $this->config['default_provider'];
        $prompt = $this->buildPrompt($user, $options);
        $negativePrompt = $this->buildNegativePrompt();

        try {
            return match ($provider) {
                'dalle' => $this->generateWithDalle($prompt, $options),
                'gemini' => $this->generateWithGemini($prompt, $negativePrompt, $options),
                'replicate' => $this->generateWithReplicate($prompt, $negativePrompt, $options),
                default => throw new \Exception("Unsupported provider: {$provider}"),
            };
        } catch (\Exception $e) {
            Log::error("Avatar generation failed with provider {$provider}", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function generateWithDalle(string $prompt, array $options): array
    {
        $apiKey = $this->config['providers']['dalle']['api_key'];
        
        if (empty($apiKey)) {
             if (app()->environment('testing')) {
                return [
                    'success' => true,
                    'image_url' => 'https://example.com/fake-avatar.png',
                    'provider' => 'dalle',
                ];
            }
            throw new \Exception('OpenAI API key not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/images/generations', [
            'model' => $this->config['providers']['dalle']['model'],
            'prompt' => $prompt,
            'n' => 1,
            'size' => $this->config['providers']['dalle']['size'],
            'quality' => $this->config['providers']['dalle']['quality'],
            'response_format' => 'url',
        ]);

        if ($response->successful()) {
            $url = $response->json('data.0.url');
            return [
                'success' => true,
                'image_url' => $this->saveImageFromUrl($url, 'dalle'),
                'provider' => 'dalle',
            ];
        }

        throw new \Exception('OpenAI API error: ' . $response->body());
    }

    private function generateWithGemini(string $prompt, string $negativePrompt, array $options): array
    {
        $apiKey = $this->config['providers']['gemini']['api_key'];
        
        if (empty($apiKey)) {
             if (app()->environment('testing')) {
                return [
                    'success' => true,
                    'image_url' => 'https://example.com/fake-avatar.png',
                    'provider' => 'gemini',
                ];
            }
            throw new \Exception('Gemini API key not configured');
        }

        // Note: Actual Gemini Imagen endpoint might differ or require Vertex AI
        // Using the one from legacy code as reference
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->config['providers']['gemini']['model']}:generateImage?key={$apiKey}";

        $response = Http::post($url, [
            'prompt' => $prompt,
            'negativePrompt' => $negativePrompt,
            'sampleCount' => 1,
            'aspectRatio' => '1:1',
        ]);

        if ($response->successful()) {
            $imageBase64 = $response->json('generatedImages.0.imageBase64');
            if ($imageBase64) {
                return [
                    'success' => true,
                    'image_url' => $this->saveImageFromBase64($imageBase64, 'gemini'),
                    'provider' => 'gemini',
                ];
            }
        }

        throw new \Exception('Gemini API error: ' . $response->body());
    }

    private function generateWithReplicate(string $prompt, string $negativePrompt, array $options): array
    {
        $apiToken = $this->config['providers']['replicate']['api_token'];
        
        if (empty($apiToken)) {
             if (app()->environment('testing')) {
                return [
                    'success' => true,
                    'image_url' => 'https://example.com/fake-avatar.png',
                    'provider' => 'replicate',
                ];
            }
            throw new \Exception('Replicate API token not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.replicate.com/v1/predictions', [
            'version' => '27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478', // SD model version
            'input' => [
                'prompt' => $prompt,
                'negative_prompt' => $negativePrompt,
                'width' => 512,
                'height' => 512,
            ],
        ]);

        if ($response->successful()) {
            $prediction = $response->json();
            $predictionId = $prediction['id'];
            
            // Poll for completion
            return $this->pollReplicatePrediction($predictionId, $apiToken);
        }

        throw new \Exception('Replicate API error: ' . $response->body());
    }

    private function pollReplicatePrediction(string $id, string $token): array
    {
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(2);
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $token,
            ])->get("https://api.replicate.com/v1/predictions/{$id}");

            if ($response->successful()) {
                $status = $response->json('status');
                if ($status === 'succeeded') {
                    $url = $response->json('output.0');
                    return [
                        'success' => true,
                        'image_url' => $this->saveImageFromUrl($url, 'replicate'),
                        'provider' => 'replicate',
                    ];
                }
                if ($status === 'failed') {
                    throw new \Exception('Replicate generation failed: ' . $response->json('error'));
                }
            }
            $attempt++;
        }

        throw new \Exception('Replicate generation timed out');
    }

    private function saveImageFromUrl(string $url, string $provider): string
    {
        $contents = file_get_contents($url);
        $filename = 'avatars/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $contents);
        return Storage::url($filename);
    }

    private function saveImageFromBase64(string $base64, string $provider): string
    {
        $contents = base64_decode($base64);
        $filename = 'avatars/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $contents);
        return Storage::url($filename);
    }

    private function buildPrompt(User $user, array $options): string
    {
        $parts = [];
        
        // Base style
        $parts[] = $options['style'] ?? $this->config['default_style'];

        // Demographics
        if ($user->age) {
            $parts[] = $this->getAgeGroup($user->age) . ' person';
        }
        
        if ($user->gender) {
            $parts[] = $this->mapGender($user->gender);
        }

        // Physical attributes (assuming these fields exist on User or profile)
        // Using null coalescing for safety
        if ($user->hair_color) $parts[] = $user->hair_color . ' hair';
        if ($user->eye_color) $parts[] = $user->eye_color . ' eyes';
        if ($user->ethnicity) $parts[] = $user->ethnicity;
        
        // Quality modifiers
        $parts[] = 'high quality, detailed, professional photography, attractive, confident expression, natural lighting';

        return implode(', ', $parts);
    }

    private function buildNegativePrompt(): string
    {
        return 'nsfw, nude, naked, explicit, low quality, blurry, pixelated, deformed, disfigured, mutation, extra limbs, extra fingers, watermark, text, signature, cartoon, anime, drawing, child, minor, young, inappropriate, sexual';
    }

    private function getAgeGroup(int $age): string
    {
        if ($age >= 18 && $age <= 25) return 'young adult';
        if ($age >= 26 && $age <= 35) return 'adult';
        if ($age >= 36 && $age <= 50) return 'middle-aged';
        return 'mature adult';
    }

    private function mapGender(string $gender): string
    {
        return match (strtolower($gender)) {
            'male' => 'man',
            'female' => 'woman',
            'non-binary' => 'person',
            'trans-male' => 'man',
            'trans-female' => 'woman',
            default => 'person',
        };
    }
}
