<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\GeohashHelper;

class GeoServiceLoadTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo:load-test {users=10000} {radius=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate concurrent user location updates and nearby queries against the Rust geo-service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = (int) $this->argument('users');
        $radiusKm = (float) $this->argument('radius');
        
        $this->info("Starting Geo-Service Load Test: {$users} users in a {$radiusKm}km radius.");
        
        // Base coordinate (e.g., Times Square, NYC)
        $baseLat = 40.7580;
        $baseLng = -73.9855;
        
        $startTime = microtime(true);
        $indexedCount = 0;
        $failedCount = 0;
        
        $baseUrl = config('services.geo_screener.url', 'http://127.0.0.1:8081');
        
        $this->info("1. Populating spatial index...");
        $bar = $this->output->createProgressBar($users);
        $bar->start();
        
        // Use a persistent multi-curl pool for high concurrency
        $pool = [];
        $batchSize = 500;
        
        for ($i = 1; $i <= $users; $i++) {
            // Randomly offset lat/lng within roughly $radiusKm
            // 1 degree lat is ~111km. 1km is ~0.009 degrees.
            $latOffset = (mt_rand(-1000, 1000) / 1000) * ($radiusKm * 0.009);
            $lngOffset = (mt_rand(-1000, 1000) / 1000) * ($radiusKm * 0.009);
            
            $lat = $baseLat + $latOffset;
            $lng = $baseLng + $lngOffset;
            
            $pool[] = [
                'user_id' => $i,
                'lat' => $lat,
                'lng' => $lng
            ];
            
            if (count($pool) >= $batchSize || $i === $users) {
                // Since Laravel Http pool is heavy, we'll just simulate the API call sequence 
                // or actually hit the Rust server if it's running.
                try {
                    $responses = Http::pool(function (\Illuminate\Http\Client\Pool $p) use ($pool, $baseUrl) {
                        $reqs = [];
                        foreach ($pool as $data) {
                            $reqs[] = $p->timeout(5)->post("{$baseUrl}/index", $data);
                        }
                        return $reqs;
                    });
                    
                    foreach ($responses as $response) {
                        if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                            $indexedCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    $failedCount += count($pool);
                }
                
                $bar->advance(count($pool));
                $pool = []; // reset
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        $indexTime = microtime(true) - $startTime;
        $this->info("Indexed {$indexedCount} users. Failed: {$failedCount}. Time: " . round($indexTime, 2) . "s");
        
        if ($indexedCount === 0) {
            $this->error("Rust geo-service appears to be down or unreachable at {$baseUrl}. Aborting query test.");
            return Command::FAILURE;
        }
        
        // Phase 2: Concurrent nearby queries
        $this->info("2. Executing concurrent nearby queries (Simulating 500 active users querying)...");
        $queryCount = 500;
        $queryRadiusMeters = 500; // 500 meters
        
        $queryStartTime = microtime(true);
        $bar2 = $this->output->createProgressBar($queryCount);
        $bar2->start();
        
        $successfulQueries = 0;
        $failedQueries = 0;
        $totalResults = 0;
        
        $queryPool = [];
        for ($i = 1; $i <= $queryCount; $i++) {
            $latOffset = (mt_rand(-1000, 1000) / 1000) * ($radiusKm * 0.009);
            $lngOffset = (mt_rand(-1000, 1000) / 1000) * ($radiusKm * 0.009);
            
            $queryPool[] = [
                'lat' => $baseLat + $latOffset,
                'lng' => $baseLng + $lngOffset,
                'radius_m' => $queryRadiusMeters
            ];
            
            if (count($queryPool) >= 100 || $i === $queryCount) {
                try {
                    $responses = Http::pool(function (\Illuminate\Http\Client\Pool $p) use ($queryPool, $baseUrl) {
                        $reqs = [];
                        foreach ($queryPool as $data) {
                            $reqs[] = $p->timeout(5)->get("{$baseUrl}/nearby", $data);
                        }
                        return $reqs;
                    });
                    
                    foreach ($responses as $response) {
                        if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                            $successfulQueries++;
                            $totalResults += count($response->json('users', []));
                        } else {
                            $failedQueries++;
                        }
                    }
                } catch (\Exception $e) {
                    $failedQueries += count($queryPool);
                }
                
                $bar2->advance(count($queryPool));
                $queryPool = [];
            }
        }
        
        $bar2->finish();
        $this->newLine();
        
        $queryTime = microtime(true) - $queryStartTime;
        $avgQueryTimeMs = ($queryTime / $queryCount) * 1000;
        $avgResultsPerQuery = $successfulQueries > 0 ? ($totalResults / $successfulQueries) : 0;
        
        $this->info("Queries completed. Success: {$successfulQueries}, Failed: {$failedQueries}. Time: " . round($queryTime, 2) . "s");
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Users Indexed', $indexedCount],
                ['Index Time', round($indexTime, 2) . 's'],
                ['Throughput (Index)', round($indexedCount / max(0.1, $indexTime), 2) . ' req/s'],
                ['Queries Executed', $queryCount],
                ['Avg Query Latency', round($avgQueryTimeMs, 2) . 'ms'],
                ['Throughput (Queries)', round($queryCount / max(0.1, $queryTime), 2) . ' req/s'],
                ['Avg Matches Returned', round($avgResultsPerQuery, 2) . ' users'],
            ]
        );
        
        if ($avgQueryTimeMs > 50) {
            $this->warn("Average query latency is above 50ms. Spatial index optimization recommended.");
        } else {
            $this->info("Performance is excellent! Spatial indexing is holding up well.");
        }
        
        return Command::SUCCESS;
    }
}