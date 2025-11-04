<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RelationshipTier;
use Illuminate\Support\Facades\DB;

class UpdateRelationshipTierDays extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tiers:update-days';

    /**
     * The console command description.
     */
    protected $description = 'Update days_connected for all relationship tiers and recalculate tiers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating relationship tier days...');

        $updated = 0;
        $upgraded = 0;

        RelationshipTier::whereNotNull('first_matched_at')
            ->chunk(100, function ($tiers) use (&$updated, &$upgraded) {
                foreach ($tiers as $tier) {
                    $previousTier = $tier->current_tier;
                    
                    // Update days connected
                    $tier->updateDaysConnected();
                    
                    // Recalculate tier based on current metrics
                    $calculatedTier = $tier->calculateTier();
                    
                    if ($calculatedTier !== $tier->current_tier) {
                        $tier->current_tier = $calculatedTier;
                        $tier->save();
                        $upgraded++;
                        
                        $this->info("Match #{$tier->match_id}: Upgraded from {$previousTier} to {$calculatedTier}");
                    }
                    
                    $updated++;
                }
            });

        $this->info("Updated {$updated} tier records");
        $this->info("{$upgraded} tiers upgraded");

        return Command::SUCCESS;
    }
}
