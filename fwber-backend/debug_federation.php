<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo '--- DEBUG: WebFinger Federation ---
';

try {
    $resource = 'acct:pelloni.robert@gmail.com';
    echo "Simulating WebFinger request for: $resource\n";

    // Replicate WebFingerController@handle logic
    if (! str_starts_with($resource, 'acct:')) {
        exit("❌ Error: Invalid resource format.\n");
    }

    $email = str_replace('acct:', '', $resource);
    $user = User::where('email', $email)->first();

    if (! $user) {
        exit("❌ Error: User not found in DB.\n");
    }

    echo '✅ User found: '.$user->name.' (ID: '.$user->id.")\n";

    // Check if ActivityPub is enabled for this user
    echo 'Is Federated: '.($user->profile->is_federated ? 'YES' : 'NO')."\n";

    $response = [
        'subject' => $resource,
        'links' => [
            [
                'rel' => 'self',
                'type' => 'application/activity+json',
                'href' => url("/api/federation/users/{$user->id}"),
            ],
        ],
    ];

    echo "✅ JSON Payload Generated Successfully:\n";
    echo json_encode($response, JSON_PRETTY_PRINT)."\n";

} catch (\Throwable $e) {
    echo '❌ FATAL ERROR: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    echo "Stack Trace:\n".substr($e->getTraceAsString(), 0, 500)."...\n";
}
