<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CacheStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display Redis cache statistics and metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('cache.default') !== 'redis') {
            $this->error('Cache driver is not set to Redis. Current driver: ' . config('cache.default'));
            return 1;
        }

        try {
            $info = Redis::info();
            
            $this->info('Redis Cache Statistics');
            $this->line('----------------------');
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Version', $info['redis_version'] ?? 'N/A'],
                    ['Uptime', ($info['uptime_in_days'] ?? 0) . ' days'],
                    ['Used Memory', $info['used_memory_human'] ?? 'N/A'],
                    ['Peak Memory', $info['used_memory_peak_human'] ?? 'N/A'],
                    ['Connected Clients', $info['connected_clients'] ?? 'N/A'],
                ]
            );

            $this->newLine();
            $this->info('Performance Metrics');
            $this->line('-------------------');

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            $ratio = $total > 0 ? round(($hits / $total) * 100, 2) : 0;

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Keyspace Hits', number_format($hits)],
                    ['Keyspace Misses', number_format($misses)],
                    ['Hit Ratio', $ratio . '%'],
                    ['Total Commands', number_format($info['total_commands_processed'] ?? 0)],
                ]
            );

            $this->newLine();
            $this->info('Key Distribution (DB Size)');
            $this->line('--------------------------');
            
            // Get DB size safely
            $dbSize = Redis::dbSize();
            $this->line("Total Keys: " . number_format($dbSize));

        } catch (\Exception $e) {
            $this->error('Failed to connect to Redis: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
