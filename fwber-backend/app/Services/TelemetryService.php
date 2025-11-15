<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class TelemetryService
{
    private bool $enabled;
    private array $schemas;

    public function __construct()
    {
        $config = Config::get('telemetry', [
            'enabled' => true,
            'schemas' => [],
        ]);
        $this->enabled = (bool)($config['enabled'] ?? true);
        $this->schemas = (array)($config['schemas'] ?? []);
    }

    public function emit(string $event, array $payload): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (isset($this->schemas[$event])) {
            $validator = Validator::make($payload, $this->schemas[$event]);
            if ($validator->fails()) {
                Log::warning('Telemetry validation failed', [
                    'event' => $event,
                    'errors' => $validator->errors()->toArray(),
                ]);
                return false;
            }
        }

        // Minimal implementation: append to log + aggregate counters
        Log::info('Telemetry event', [
            'event' => $event,
            'payload' => $payload,
            'ts' => now()->toISOString(),
        ]);

        $this->aggregate($event, $payload);
        return true;
    }

    private function aggregate(string $event, array $payload): void
    {
        $key = 'telemetry:counts';
        $counts = Cache::get($key, []);
        $counts[$event] = ($counts[$event] ?? 0) + 1;
        Cache::put($key, $counts, 3600);
    }

    public function getCounts(): array
    {
        return Cache::get('telemetry:counts', []);
    }
}
