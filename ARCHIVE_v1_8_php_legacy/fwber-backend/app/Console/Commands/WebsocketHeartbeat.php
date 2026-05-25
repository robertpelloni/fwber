<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebsocketHeartbeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the websocket heartbeat cache key to confirm Reverb is active.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // This command should be scheduled to run every minute
        // It confirms the backend task runner is alive and can talk to Redis
        Cache::put('reverb_last_heartbeat', now(), 120); // 2 minute TTL
        
        $this->info('Websocket heartbeat updated: ' . now()->toIso8601String());
    }
}
