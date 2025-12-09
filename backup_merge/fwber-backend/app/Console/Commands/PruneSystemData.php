<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProximityArtifact;
use App\Models\Notification;
use Illuminate\Support\Facades\Artisan;

class PruneSystemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:prune-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune ephemeral system data according to retention policy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting system data pruning...');

        // 1. Delete ProximityArtifact records older than 24 hours
        $countArtifacts = ProximityArtifact::where('created_at', '<', now()->subHours(24))->delete();
        $this->info("Deleted {$countArtifacts} old ProximityArtifact records.");

        // 2. Delete Notification records older than 30 days
        $countNotifications = Notification::where('created_at', '<', now()->subDays(30))->delete();
        $this->info("Deleted {$countNotifications} old Notification records.");

        // 3. Prune Telescope entries (if installed) older than 48 hours
        // We check if the command exists in the list of commands or if the class exists
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
             $this->info('Pruning Telescope entries...');
             $this->call('telescope:prune', ['--hours' => 48]);
        } else {
            $this->info('Telescope not installed, skipping prune.');
        }

        $this->info('System data pruning completed.');
    }
}
