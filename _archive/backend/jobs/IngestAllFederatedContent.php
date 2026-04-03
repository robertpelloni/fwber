<?php

namespace App\Jobs;

use App\Models\Following;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestAllFederatedContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // 1. Sync Reputations
        SyncFederatedReputation::dispatch();

        // 2. Fetch outboxes for all active follows
        $actors = Following::where('status', 'accepted')
            ->distinct('actor_uri')
            ->pluck('actor_uri');

        foreach ($actors as $uri) {
            FetchRemoteOutbox::dispatch($uri);
        }
    }
}
