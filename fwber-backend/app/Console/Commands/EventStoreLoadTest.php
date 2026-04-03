<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventStoreLoadTest extends Command
{
    protected $signature = 'event-store:load-test {count=100000} {--batch=1000}';
    protected $description = 'Load test the Event Store with a massive amount of records to check index performance.';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $batchSize = (int) $this->option('batch');
        
        $this->info("🚀 Starting Load Test for Event Store with {$count} records...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $types = ['UserLocation', 'Chatroom', 'MatchAction', 'UserProfile'];
        
        for ($i = 0; $i < $count; $i += $batchSize) {
            $batch = [];
            for ($j = 0; $j < $batchSize; $j++) {
                if ($i + $j >= $count) break;
                
                $batch[] = [
                    'aggregate_uuid' => Str::uuid()->toString(),
                    'aggregate_type' => $types[array_rand($types)],
                    'version' => rand(1, 100),
                    'event_type' => 'App\Events\LoadTestEvent',
                    'payload' => json_encode(['data' => Str::random(50)]),
                    'metadata' => json_encode(['ip' => '127.0.0.1']),
                    'recorded_at' => now()->toDateTimeString(),
                ];
            }
            DB::table('domain_events')->insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Seeded {$count} events.");

        $this->runPerformanceTests();
    }

    private function runPerformanceTests()
    {
        $this->info("📊 Running Performance Tests...");

        // Test 1: Random ID Lookup (Simulating getCurrentVersion)
        $randomId = DB::table('domain_events')->inRandomOrder()->first();
        if (!$randomId) return;

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            DB::table('domain_events')
                ->where('aggregate_uuid', $randomId->aggregate_uuid)
                ->where('aggregate_type', $randomId->aggregate_type)
                ->max('version');
        }
        $duration = (microtime(true) - $start) / 100;
        $this->info("⏱️ Avg. getCurrentVersion time: " . number_format($duration * 1000, 4) . "ms");

        // Test 2: Fetch full stream
        $start = microtime(true);
        DB::table('domain_events')
            ->where('aggregate_uuid', $randomId->aggregate_uuid)
            ->orderBy('version', 'asc')
            ->get();
        $duration = microtime(true) - $start;
        $this->info("⏱️ Fetch stream (single aggregate) time: " . number_format($duration * 1000, 4) . "ms");

        // Test 3: Unique constraint check (attempt duplicate)
        try {
            $this->info("🧪 Testing Unique Constraint...");
            DB::table('domain_events')->insert([
                'aggregate_uuid' => $randomId->aggregate_uuid,
                'aggregate_type' => $randomId->aggregate_type,
                'version' => $randomId->version,
                'event_type' => $randomId->event_type,
                'payload' => $randomId->payload,
                'recorded_at' => $randomId->recorded_at,
            ]);
            $this->error("❌ FAIL: Unique constraint did not prevent duplicate version!");
        } catch (\Exception $e) {
            $this->info("✅ PASS: Duplicate version rejected.");
        }
    }
}
