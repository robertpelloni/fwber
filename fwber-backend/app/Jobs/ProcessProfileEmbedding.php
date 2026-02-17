<?php

namespace App\Jobs;

use App\Models\UserProfile;
use App\Services\VectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProfileEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    /**
     * Create a new job instance.
     */
    public function __construct(UserProfile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     */
    public function handle(VectorService $vectorService): void
    {
        // Ensure index exists (idempotent)
        $vectorService->initializeIndex();
        
        // Generate and store embedding
        $vectorService->storeProfile($this->profile);
    }
}
