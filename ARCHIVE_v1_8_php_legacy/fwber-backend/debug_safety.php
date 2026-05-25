<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SafeWalk;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "--- DEBUG: Safety/Walk/Active ---\n";

try {
    // We pick the first user to simulate the request
    $user = User::first();
    if (! $user) {
        exit("❌ Error: No users found in database.\n");
    }

    Auth::login($user);
    echo 'Simulating request for User: '.$user->email."\n";

    $walk = SafeWalk::where('user_id', $user->id)
        ->active()
        ->latest()
        ->first();

    echo "✅ Success: Query executed.\n";
    echo 'Result: '.json_encode($walk)."\n";

} catch (\Throwable $e) {
    echo '❌ FATAL ERROR: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    echo "Stack Trace:\n".$e->getTraceAsString()."\n";
}
