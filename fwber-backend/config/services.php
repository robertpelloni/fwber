<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'mock'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
    ],

    'mercure' => [
        'public_url' => env('MERCURE_PUBLIC_URL', 'https://demo.mercure.rocks/.well-known/mercure'),
        'internal_url' => env('MERCURE_INTERNAL_URL', 'https://demo.mercure.rocks/.well-known/mercure'),
        'publisher_key' => env('MERCURE_PUBLISHER_JWT_KEY', '!ChangeThisMercureHubJWTSecretKey!'),
        'subscriber_key' => env('MERCURE_SUBSCRIBER_JWT_KEY', '!ChangeThisMercureHubJWTSecretKey!'),
        'cookie_domain' => env('MERCURE_COOKIE_DOMAIN'),
    ],

    'solana' => [
        'server_secret_key' => env('SERVER_WALLET_SECRET') ?? env('SOLANA_SERVER_SECRET_KEY'),
        'mint_address' => env('MINT_ADDRESS') ?? env('SOLANA_MINT_ADDRESS'),
        'rpc_url' => env('SOLANA_RPC_URL'),
    ],

    'google' => [
        'vision' => [
            'enabled' => env('GOOGLE_VISION_ENABLED', false),
            'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ],
    ],

];
