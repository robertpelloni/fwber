<?php

namespace App\Console\Commands;

use App\Services\ShadowThrottleService;
use Illuminate\Console\Command;

class PruneExpiredThrottles extends Command
{
    protected $signature = 'throttles:prune';
    protected $description = 'Remove expired shadow throttles';

    public function handle(ShadowThrottleService $service): int
    {
        $count = $service->pruneExpired();
        $this->info("Pruned {$count} expired throttles");
        
        return 0;
    }
}
