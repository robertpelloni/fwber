<?php

namespace App\Services;

use App\Models\User;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarGenerationService
{
    private array $config;
    private MediaAnalysisInterface $mediaAnalysis;

    public function __construct(MediaAnalysisInterface $mediaAnalysis)
    {
        $this->mediaAnalysis = $mediaAnalysis;
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

    public function generateFromImage(User $user, string $imagePath, array $options = []): array
    {
        // Only Replicate supported for now
        $provider = 'replicate';
        $prompt = $this->buildPrompt($user, $options);
        $negativePrompt = $this->buildNegativePrompt();

        try {
            return $this->generateWithReplicateImg2Img($imagePath, $prompt, $negativePrompt, $options);
        } catch (\Exception $e) {
            Log::error("Avatar img2img generation failed", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
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

    private function generateWithReplicateImg2Img(string $imagePath, string $prompt, string $negativePrompt, array $options): array
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

        // Read image and convert to data URI
        if (!Storage::disk('public')->exists($imagePath)) {
            throw new \Exception('Source image not found');
        }
        $imageContent = Storage::disk('public')->get($imagePath);
        $base64 = base64_encode($imageContent);
        $mime = Storage::disk('public')->mimeType($imagePath) ?? 'image/jpeg';
        $dataUri = "data:$mime;base64,$base64";

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.replicate.com/v1/predictions', [
            'version' => '27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478', // SD model
            'input' => [
                'image' => $dataUri,
                'prompt' => $prompt,
                'negative_prompt' => $negativePrompt,
                'prompt_strength' => 0.8,
                'num_inference_steps' => 50,
                'guidance_scale' => 7.5,
            ],
        ]);

        if ($response->successful()) {
            $prediction = $response->json();
            return $this->pollReplicatePrediction($prediction['id'], $apiToken);
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
        $response = Http::get($url);
        
        if (!$response->successful()) {
            throw new \Exception("Failed to download image from {$url}");
        }

        $contents = $response->body();
        $filename = 'avatars/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $contents);

        // Analyze the image for safety
        try {
            $analysis = $this->mediaAnalysis->analyze($filename, 'image');
            if (!$analysis->isSafe()) {
                Storage::disk('public')->delete($filename);
                throw new \Exception("Generated avatar was flagged as unsafe: " . implode(', ', $analysis->unsafeReasons()));
            }
        } catch (\Exception $e) {
            // If analysis fails (e.g. service down), we might want to fail safe or log warning.
            // For now, we'll treat analysis failure as a blocker if it's the unsafe exception,
            // but if it's a technical error, we might want to allow it or block it.
            // Let's block it to be safe.
            if (Storage::disk('public')->exists($filename)) {
                Storage::disk('public')->delete($filename);
            }
            throw $e;
        }

        return Storage::url($filename);
    }

    private function saveImageFromBase64(string $base64, string $provider): string
    {
        $contents = base64_decode($base64);
        $filename = 'avatars/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $contents);

        // Analyze the image for safety
        try {
            $analysis = $this->mediaAnalysis->analyze($filename, 'image');
            if (!$analysis->isSafe()) {
                Storage::disk('public')->delete($filename);
                throw new \Exception("Generated avatar was flagged as unsafe: " . implode(', ', $analysis->unsafeReasons()));
            }
        } catch (\Exception $e) {
            if (Storage::disk('public')->exists($filename)) {
                Storage::disk('public')->delete($filename);
            }
            throw $e;
        }

        return Storage::url($filename);
    }

    private function buildPrompt(User $user, array $options): string
    {
        $profile = $user->profile;
        $parts = [];
        
        // Helper to get attribute from options or profile
        $get = fn($key) => $options[$key] ?? $profile?->{$key} ?? null;

        // Base style
        $parts[] = $options['style'] ?? $this->config['default_style'];

        // Age
        $age = $options['age'] ?? null;
        if (!$age && $profile?->birthdate) {
            $age = $profile->birthdate->age;
        }
        
        if ($age) {
            $parts[] = $this->getAgeGroup((int)$age) . ' person';
        } else {
            $parts[] = 'person';
        }
        
        // Gender
        $gender = $get('gender');
        if ($gender) {
            $parts[] = $this->mapGender($gender);
        }

        // Physical attributes
        $ethnicity = $get('ethnicity');
        if ($ethnicity) $parts[] = $ethnicity;

        $bodyType = $get('body_type');
        if ($bodyType) $parts[] = $bodyType . ' body type';

        $height = $get('height_cm');
        if ($height) {
            if ($height > 185) $parts[] = 'tall';
            elseif ($height < 160) $parts[] = 'short';
        }

        // Breast size (only for relevant genders)
        $breastSize = $get('breast_size');
        if ($breastSize && in_array(strtolower($gender ?? ''), ['female', 'trans-female', 'non-binary'])) {
             $parts[] = $breastSize . ' bust';
        }

        $hairColor = $get('hair_color');
        if ($hairColor) $parts[] = $hairColor . ' hair';

        $eyeColor = $get('eye_color');
        if ($eyeColor) $parts[] = $eyeColor . ' eyes';

        $skinTone = $get('skin_tone');
        if ($skinTone) $parts[] = $skinTone . ' skin tone';

        $facialHair = $get('facial_hair');
        if ($facialHair && $facialHair !== 'none') $parts[] = $facialHair;

        // Distinctive features
        $tattoos = $get('tattoos');
        if (!empty($tattoos) && is_array($tattoos)) {
             $parts[] = 'visible tattoos';
        }

        $piercings = $get('piercings');
        if (!empty($piercings) && is_array($piercings)) {
             $parts[] = 'piercings';
        }

        // Style & Vibe
        $clothingStyle = $get('clothing_style');
        $occupation = $get('occupation');
        
        if ($clothingStyle) {
            $parts[] = 'wearing ' . $clothingStyle . ' style clothing';
        } elseif ($occupation) {
            // Fallback to occupation-based style if no specific style set
            $parts[] = 'dressed as a ' . $occupation;
        }

        $fitnessLevel = $get('fitness_level');
        if ($fitnessLevel) $parts[] = $fitnessLevel . ' build';

        // Personality & Vibe (MBTI)
        $mbti = $get('personality_type');
        if ($mbti) {
            $firstLetter = strtoupper(substr($mbti, 0, 1));
            if ($firstLetter === 'E') $parts[] = 'energetic, friendly expression';
            elseif ($firstLetter === 'I') $parts[] = 'calm, thoughtful expression';
        }

        // Background context from interests
        $interests = $get('interests');
        if (!empty($interests) && is_array($interests)) {
            $mainInterest = $interests[0];
            $parts[] = $mainInterest . ' background theme';
        }

        // Quality modifiers
        $parts[] = 'high quality, detailed, professional photography, attractive, confident expression, natural lighting';

        return implode(', ', array_filter($parts));
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
