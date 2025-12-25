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
        'public_url' => env('MERCURE_PUBLIC_URL', 'https://api.fwber.me/.well-known/mercure'),
        'internal_url' => env('MERCURE_INTERNAL_URL', 'http://fwber-mercure/.well-known/mercure'),
        // Hardcoded keys to match running Mercure container (env vars are incorrect in container)
        'publisher_key' => 'uWnEOi51TibZqRn3YbRMvu0XbZwWp42X6z6s0aZMcAw=',
        'subscriber_key' => '0DyZctGxb2WUcwL3XH0HFq+5XWgnEI9ojHn5Y2cY3ic=',
        'cookie_domain' => env('MERCURE_COOKIE_DOMAIN'),
    ],

];
