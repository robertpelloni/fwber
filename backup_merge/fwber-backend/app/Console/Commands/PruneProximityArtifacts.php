<?php

namespace App\Console\Commands;

use App\Services\ProximityArtifactService;
use Illuminate\Console\Command;

class PruneProximityArtifacts extends Command
{
    protected $signature = 'proximity:prune';
    protected $description = 'Delete expired proximity artifacts';

    public function handle(ProximityArtifactService $service): int
    {
        $count = $service->pruneExpired();
        $this->info("Pruned {$count} expired artifacts.");
        return Command::SUCCESS;
    }
}
