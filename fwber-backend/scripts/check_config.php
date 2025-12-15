<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checks = [
    'App' => [
        'APP_KEY' => 'Critical security key',
        'APP_URL' => 'Application URL',
    ],
    'Database' => [
        'DB_CONNECTION' => 'Database driver',
        'DB_HOST' => 'Database host',
        'DB_DATABASE' => 'Database name',
    ],
    'Redis' => [
        'REDIS_HOST' => 'Redis host',
    ],
    'Mail' => [
        'MAIL_MAILER' => 'Mail driver',
    ],
    'AWS' => [
        'AWS_ACCESS_KEY_ID' => 'AWS Access Key',
        'AWS_SECRET_ACCESS_KEY' => 'AWS Secret Key',
        'AWS_BUCKET' => 'S3 Bucket',
        'AWS_DEFAULT_REGION' => 'AWS Region',
    ],
    'Stripe' => [
        'STRIPE_KEY' => 'Stripe Public Key',
        'STRIPE_SECRET' => 'Stripe Secret Key',
        'STRIPE_WEBHOOK_SECRET' => 'Stripe Webhook Secret',
    ],
    'AI' => [
        'OPENAI_API_KEY' => 'OpenAI API Key',
        'ANTHROPIC_API_KEY' => 'Anthropic API Key',
        'GEMINI_API_KEY' => 'Gemini API Key',
    ],
    'Push Notifications' => [
        'VAPID_PUBLIC_KEY' => 'VAPID Public Key',
        'VAPID_PRIVATE_KEY' => 'VAPID Private Key',
    ],
    'Mercure' => [
        'MERCURE_PUBLIC_URL' => 'Mercure Public URL',
        'MERCURE_PUBLISHER_JWT_KEY' => 'Publisher JWT Key',
        'MERCURE_SUBSCRIBER_JWT_KEY' => 'Subscriber JWT Key',
    ],
    'Sentry' => [
        'SENTRY_LARAVEL_DSN' => 'Sentry DSN',
    ],
    'Feature Flags' => [
        'FEATURE_AI_WINGMAN' => 'AI Wingman',
        'FEATURE_MEDIA_ANALYSIS' => 'Media Analysis',
        'FEATURE_VIDEO_CHAT' => 'Video Chat',
        'FEATURE_CHATROOMS' => 'Chatrooms',
    ],
];

echo "Checking Environment Configuration...\n\n";

foreach ($checks as $category => $vars) {
    echo "[$category]\n";
    foreach ($vars as $key => $description) {
        $value = env($key);
        $status = $value ? "✅ Set" : "❌ Missing";
        $masked = $value ? substr($value, 0, 4) . '...' : 'N/A';
        
        // Don't mask non-secrets
        if (in_array($key, ['APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'REDIS_HOST', 'MAIL_MAILER', 'AWS_DEFAULT_REGION', 'AWS_BUCKET', 'MERCURE_PUBLIC_URL'])) {
            $masked = $value ?: 'N/A';
        }

        printf("  %-25s %-10s %-20s (%s)\n", $key, $status, $masked, $description);
    }
    echo "\n";
}
