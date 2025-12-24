<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;

// Load Laravel application context if running standalone
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'pelloni.robert@gmail.com';

echo "--- Fixing User State for $email ---\n";

try {
    $user = User::where('email', $email)->first();

    if (!$user) {
        echo "❌ User not found!\n";
        exit(1);
    }

    echo "✅ User found (ID: {$user->id})\n";

    // 1. Fix Profile
    if (!$user->profile) {
        echo "⚠️  Profile missing. Creating default profile...\n";
        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->display_name = $user->name;
        $profile->gender = 'prefer-not-to-say'; // Default
        $profile->birthdate = '2000-01-01'; // Default valid date
        $profile->save();
        echo "✅ Profile created.\n";
    } else {
        echo "✅ Profile exists.\n";
    }

    // 2. Fix Photos (Optional, just ensure no corruption)
    $photos = $user->photos;
    echo "ℹ️  User has " . $photos->count() . " photos.\n";

    echo "--- Fix Complete ---\n";

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
