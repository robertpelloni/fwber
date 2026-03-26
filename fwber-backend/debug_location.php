<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UserLocation;
use App\Domain\Core\EventSourcing\EventStore;
use App\Events\Location\UserLocationUpdated;
use Illuminate\Support\Facades\Auth;

echo "--- DEBUG: Location Update Logic ---
";

try {
    $user = User::first();
    if (!$user) {
        die("❌ Error: No users found.\n");
    }
    
    echo "Simulating Location Update for User: " . $user->email . "\n";

    $eventStore = app(EventStore::class);
    echo "EventStore Resolved OK.\n";

    $aggregateId = (string)$user->id;
    $currentVersion = $eventStore->getCurrentVersion($aggregateId, 'UserLocation');
    echo "Current Version: $currentVersion\n";

    $event = new UserLocationUpdated(
        $aggregateId,
        42.3314,
        -83.0458,
        "Detroit Hub"
    );
    echo "Event Object Created OK.\n";

    echo "Appending to EventStore...\n";
    $eventStore->append(
        $event, 
        'UserLocation', 
        $currentVersion + 1,
        ['ip' => '127.0.0.1', 'user_agent' => 'StressTest']
    );
    echo "✅ EventStore Append Success!\n";

    echo "Updating Projection...\n";
    $location = UserLocation::where('user_id', $user->id)->first() ?? new UserLocation(['user_id' => $user->id]);
    $location->latitude = 42.3314;
    $location->longitude = -83.0458;
    $location->last_updated = now();
    $location->save();
    echo "✅ Projection Saved OK!\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
