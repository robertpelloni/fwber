<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GeoScreenerClient;
use Illuminate\Support\Facades\Log;

class SyncLocationToGeoScreener implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $lat;
    protected $lng;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, float $lat, float $lng)
    {
        $this->userId = $userId;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * Execute the job.
     */
    public function handle(GeoScreenerClient $geoScreener): void
    {
        // Only attempt sync if the screener is actually enabled in the environment
        if (!$geoScreener->isEnabled()) {
            return;
        }

        $success = $geoScreener->indexLocation($this->userId, $this->lat, $this->lng);

        if (!$success) {
            Log::warning("Failed to sync location for user {$this->userId} to Rust Geo-Screener.");
            // We could optionally throw an exception here to let the worker retry, 
            // but for a spatial cache it's often better to just drop it and wait for the next ping.
        }
    }
}
