<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ConsumeFederatedEvents extends Command
{
    protected $signature = 'events:consume-federated {--stream=fwber:events:all}';
    protected $description = 'Consume domain events from the distributed relay and sync local state.';

    public function handle()
    {
        $stream = $this->option('stream');
        $this->info("🛰️ Starting Federated Event Consumer on stream: {$stream}");

        // Use a Consumer Group for distributed scaling
        $groupName = 'fwber_sync_nodes';
        $consumerName = 'node_' . gethostname();

        try {
            Redis::xgroup('CREATE', $stream, $groupName, '0', true);
        } catch (\Exception $e) {
            // Group likely already exists
        }

        while (true) {
            // Read new events
            $entries = Redis::xreadgroup($groupName, $consumerName, [$stream => '>'], 1, 5000);

            if (empty($entries)) {
                $this->line("Waiting for events...");
                continue;
            }

            foreach ($entries[$stream] as $id => $payload) {
                $this->processEvent($id, $payload);
                Redis::xack($stream, $groupName, [$id]);
            }
        }
    }

    protected function processEvent(string $id, array $payload): void
    {
        $eventType = $payload['event_type'] ?? 'Unknown';
        $aggregateUuid = $payload['aggregate_uuid'] ?? 'Unknown';

        $this->info("📥 Processing {$eventType} for Aggregate {$aggregateUuid} (ID: {$id})");

        // Logic for replaying events into local projections
        // For example: Update local trust scores, refresh cached profiles, etc.
        Log::info("Federation Sync: Replayed event {$id}", $payload);
    }
}
