<?php

namespace App\Jobs;

use App\Models\Following;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFederatedReputation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Get unique external actors we are following
        $actors = Following::distinct('actor_uri')->pluck('actor_uri');

        foreach ($actors as $uri) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Accept' => 'application/activity+json'])
                    ->get($uri);

                if ($response->successful()) {
                    $actor = $response->json();
                    
                    if (isset($actor['reputation'])) {
                        $reputation = $actor['reputation'];
                        
                        DB::table('federated_actor_reputations')->updateOrInsert(
                            ['actor_uri' => $uri],
                            [
                                'vouch_count' => $reputation['vouchCount'] ?? 0,
                                'member_since' => isset($reputation['memberSince']) ? date('Y-m-d H:i:s', strtotime($reputation['memberSince'])) : null,
                                'reputation_metadata' => json_encode($reputation['metadata'] ?? []),
                                'last_synced_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Reputation Sync: Failed for {$uri}: ".$e->getMessage());
            }
        }
    }
}
