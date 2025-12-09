<?php

namespace App\Jobs;

use App\Models\Boost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpireBoosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Mark all expired boosts as expired.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        // Find all active boosts that have expired
        $expiredBoosts = Boost::where('status', 'active')
            ->where('expires_at', '<=', $now)
            ->get();

        $count = $expiredBoosts->count();

        if ($count === 0) {
            Log::info('ExpireBoosts: No boosts to expire');
            return;
        }

        // Mark each boost as expired
        foreach ($expiredBoosts as $boost) {
            $boost->status = 'expired';
            $boost->save();
            
            Log::info("ExpireBoosts: Expired boost {$boost->id} for user {$boost->user_id}");
        }

        Log::info("ExpireBoosts: Successfully expired {$count} boost(s)");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExpireBoosts job failed: ' . $exception->getMessage());
    }
}
