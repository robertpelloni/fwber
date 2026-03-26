<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SafeWalk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "--- DEEP DEBUG: Safety/Walk/Active ---\n";

try {
    $user = User::first();
    if (!$user) {
        die("❌ Error: No users found.\n");
    }
    
    Auth::login($user);
    echo "User: " . $user->email . " (ID: " . $user->id . ")\n";

    echo "Testing Database Connection...\n";
    DB::connection()->getPdo();
    echo "✅ DB OK\n";

    echo "Testing SafeWalk Query...\n";
    // Manually trigger the scope to see if it's the issue
    $query = SafeWalk::where('user_id', $user->id);
    echo "Base Query: " . $query->toSql() . "\n";
    
    $query->active();
    echo "Scoped Query: " . $query->toSql() . "\n";

    $walk = $query->latest()->first();
    echo "✅ Query Finished.\n";
    echo "Result: " . ($walk ? "Walk ID " . $walk->id : "No active walk") . "\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . substr($e->getTraceAsString(), 0, 500) . "...\n";
}
