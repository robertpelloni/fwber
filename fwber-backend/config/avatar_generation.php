<?php

return [
    'enabled' => env('AVATAR_GENERATION_ENABLED', true),
    'default_provider' => env('AVATAR_GENERATION_PROVIDER', 'dalle'),
    
    'providers' => [
        'dalle' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('DALLE_MODEL', 'dall-e-3'),
            'size' => '1024x1024',
            'quality' => 'standard',
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => 'imagen-3.0-generate-001',
        ],
        'replicate' => [
            'api_token' => env('REPLICATE_API_TOKEN'),
            'model' => 'stability-ai/stable-diffusion:27b93a2413e7f36cd83da926f3656280b2931564ff050bf9575f1fdf9bcd7478',
        ],
    ],
    
    'default_style' => 'realistic portrait, professional headshot',
];
