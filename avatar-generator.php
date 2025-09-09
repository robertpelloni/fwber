<?php
/*
    AI Avatar Generation System
    Generates custom avatars based on user profile attributes
    Supports multiple AI providers (Replicate, OpenAI DALL-E, local Stable Diffusion)
*/

class AvatarGenerator {
    
    private $providers = [
        'replicate' => 'ReplicateProvider',
        'dalle' => 'DALLEProvider',
        'local' => 'LocalSDProvider',
        'comfyui' => 'ComfyUIProvider'
    ];
    
    private $currentProvider;
    private $config;
    
    public function __construct($provider = 'replicate', $config = []) {
        $this->config = array_merge([
            'replicate_token' => '', // Set in environment
            'openai_api_key' => '', // Set in environment
            'local_sd_url' => 'http://127.0.0.1:7860',
            'comfyui_url' => 'http://127.0.0.1:8188',
            'default_style' => 'realistic portrait',
            'image_size' => '512x512',
            'steps' => 30,
            'guidance_scale' => 7.5
        ], $config);
        
        $this->setProvider($provider);
    }
    
    public function setProvider($provider) {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Unsupported provider: $provider");
        }
        
        $providerClass = $this->providers[$provider];
        $this->currentProvider = new $providerClass($this->config);
        return $this;
    }
    
    public function generateAvatar($userProfile, $options = []) {
        $prompt = $this->buildPrompt($userProfile, $options);
        $negativePrompt = $this->buildNegativePrompt();
        
        $parameters = array_merge([
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'width' => 512,
            'height' => 512,
            'num_inference_steps' => $this->config['steps'],
            'guidance_scale' => $this->config['guidance_scale'],
            'seed' => $this->generateSeed($userProfile)
        ], $options);
        
        return $this->currentProvider->generate($parameters);
    }
    
    private function buildPrompt($profile, $options = []) {
        $promptParts = [];
        
        // Base style
        $style = $options['style'] ?? $this->config['default_style'];
        $promptParts[] = $style;
        
        // Demographics
        if (isset($profile['age'])) {
            $ageGroup = $this->getAgeGroup($profile['age']);
            $promptParts[] = $ageGroup . ' person';
        }
        
        if (isset($profile['gender'])) {
            $promptParts[] = $this->mapGender($profile['gender']);
        }
        
        // Physical attributes
        if (isset($profile['hair_color'])) {
            $promptParts[] = $profile['hair_color'] . ' hair';
        }
        
        if (isset($profile['hair_style'])) {
            $promptParts[] = $profile['hair_style'] . ' hairstyle';
        }
        
        if (isset($profile['eye_color'])) {
            $promptParts[] = $profile['eye_color'] . ' eyes';
        }
        
        if (isset($profile['ethnicity'])) {
            $promptParts[] = $this->mapEthnicity($profile['ethnicity']);
        }
        
        if (isset($profile['body_type'])) {
            $promptParts[] = $this->mapBodyType($profile['body_type']);
        }
        
        // Style preferences
        if (isset($profile['style'])) {
            $promptParts[] = $profile['style'] . ' style';
        }
        
        // Quality modifiers
        $promptParts[] = 'high quality, detailed, professional photography';
        $promptParts[] = 'attractive, confident expression';
        
        // Clothing (appropriate)
        $clothing = $options['clothing'] ?? $this->getDefaultClothing($profile);
        $promptParts[] = $clothing;
        
        return implode(', ', $promptParts);
    }
    
    private function buildNegativePrompt() {
        return implode(', ', [
            'nsfw, nude, naked, explicit',
            'low quality, blurry, pixelated',
            'deformed, disfigured, mutation',
            'extra limbs, extra fingers',
            'watermark, text, signature',
            'cartoon, anime, drawing',
            'child, minor, young',
            'inappropriate, sexual'
        ]);
    }
    
    private function getAgeGroup($age) {
        if ($age >= 18 && $age <= 25) return 'young adult';
        if ($age >= 26 && $age <= 35) return 'adult';
        if ($age >= 36 && $age <= 50) return 'middle-aged';
        return 'mature adult';
    }
    
    private function mapGender($gender) {
        $mapping = [
            'male' => 'man',
            'female' => 'woman',
            'non-binary' => 'person',
            'trans-male' => 'man',
            'trans-female' => 'woman'
        ];
        return $mapping[strtolower($gender)] ?? 'person';
    }
    
    private function mapEthnicity($ethnicity) {
        $mapping = [
            'caucasian' => 'caucasian',
            'african' => 'african',
            'asian' => 'asian',
            'hispanic' => 'hispanic',
            'middle_eastern' => 'middle eastern',
            'mixed' => 'mixed ethnicity',
            'other' => ''
        ];
        return $mapping[strtolower($ethnicity)] ?? '';
    }
    
    private function mapBodyType($bodyType) {
        $mapping = [
            'slim' => 'slim build',
            'athletic' => 'athletic build',
            'average' => 'average build',
            'curvy' => 'curvy',
            'plus_size' => 'plus size',
            'muscular' => 'muscular'
        ];
        return $mapping[strtolower($bodyType)] ?? '';
    }
    
    private function getDefaultClothing($profile) {
        // Generate appropriate clothing based on context
        $options = [
            'casual shirt',
            'nice top',
            'fashionable outfit',
            'stylish clothing',
            'contemporary fashion'
        ];
        
        return $options[array_rand($options)];
    }
    
    private function generateSeed($profile) {
        // Generate consistent seed based on user profile for reproducible results
        $profileString = serialize($profile);
        return abs(crc32($profileString)) % 2147483647;
    }
}

// Provider implementations
class ReplicateProvider {
    private $config;
    private $apiUrl = 'https://api.replicate.com/v1/predictions';
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function generate($parameters) {
        $model = 'stability-ai/stable-diffusion:27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478';
        
        $payload = [
            'version' => '27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478',
            'input' => $parameters
        ];
        
        $headers = [
            'Authorization: Token ' . $this->config['replicate_token'],
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception("Replicate API error: HTTP $httpCode - $response");
        }
        
        $result = json_decode($response, true);
        return $this->waitForCompletion($result['id']);
    }
    
    private function waitForCompletion($predictionId) {
        $maxAttempts = 60; // 5 minutes max
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $status = $this->getStatus($predictionId);
            
            if ($status['status'] === 'succeeded') {
                return [
                    'success' => true,
                    'image_url' => $status['output'][0] ?? null,
                    'provider' => 'replicate'
                ];
            }
            
            if ($status['status'] === 'failed') {
                return [
                    'success' => false,
                    'error' => $status['error'] ?? 'Generation failed',
                    'provider' => 'replicate'
                ];
            }
            
            sleep(5);
            $attempt++;
        }
        
        return [
            'success' => false,
            'error' => 'Timeout waiting for generation',
            'provider' => 'replicate'
        ];
    }
    
    private function getStatus($predictionId) {
        $url = $this->apiUrl . '/' . $predictionId;
        $headers = ['Authorization: Token ' . $this->config['replicate_token']];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class LocalSDProvider {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function generate($parameters) {
        $url = $this->config['local_sd_url'] . '/sdapi/v1/txt2img';
        
        $payload = [
            'prompt' => $parameters['prompt'],
            'negative_prompt' => $parameters['negative_prompt'],
            'width' => $parameters['width'],
            'height' => $parameters['height'],
            'steps' => $parameters['num_inference_steps'],
            'cfg_scale' => $parameters['guidance_scale'],
            'seed' => $parameters['seed'],
            'sampler_name' => 'DPM++ 2M Karras'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "Local SD API error: HTTP $httpCode",
                'provider' => 'local'
            ];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['images'][0])) {
            // Save base64 image
            $imageData = base64_decode($result['images'][0]);
            $filename = 'avatar_' . uniqid() . '.png';
            $filepath = '/avatars/' . $filename;
            
            file_put_contents('.' . $filepath, $imageData);
            
            return [
                'success' => true,
                'image_url' => $filepath,
                'provider' => 'local'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'No image generated',
            'provider' => 'local'
        ];
    }
}

class ComfyUIProvider {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function generate($parameters) {
        // ComfyUI workflow implementation
        // This would require a more complex workflow setup
        return [
            'success' => false,
            'error' => 'ComfyUI provider not yet implemented',
            'provider' => 'comfyui'
        ];
    }
}

class DALLEProvider {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function generate($parameters) {
        // DALL-E API implementation
        $url = 'https://api.openai.com/v1/images/generations';
        
        $payload = [
            'prompt' => $parameters['prompt'],
            'n' => 1,
            'size' => $parameters['width'] . 'x' . $parameters['height'],
            'quality' => 'standard'
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->config['openai_api_key'],
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => "OpenAI API error: HTTP $httpCode - $response",
                'provider' => 'dalle'
            ];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['data'][0]['url'])) {
            return [
                'success' => true,
                'image_url' => $result['data'][0]['url'],
                'provider' => 'dalle'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'No image generated',
            'provider' => 'dalle'
        ];
    }
}

// Usage example:
/*
$generator = new AvatarGenerator('replicate', [
    'replicate_token' => 'your_replicate_token_here'
]);

$userProfile = [
    'age' => 25,
    'gender' => 'female',
    'hair_color' => 'blonde',
    'hair_style' => 'long',
    'eye_color' => 'blue',
    'ethnicity' => 'caucasian',
    'body_type' => 'athletic'
];

$result = $generator->generateAvatar($userProfile);
if ($result['success']) {
    echo "Avatar generated: " . $result['image_url'];
} else {
    echo "Error: " . $result['error'];
}
*/
?>