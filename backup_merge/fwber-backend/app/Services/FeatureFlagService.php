<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class FeatureFlagService
{
    private bool $enabled;
    private array $flags;

    public function __construct()
    {
        $config = Config::get('feature_flags', [
            'enabled' => true,
            'flags' => [],
        ]);
        $this->enabled = (bool)($config['enabled'] ?? true);
        $this->flags = (array)($config['flags'] ?? []);
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function isEnabled(string $flag): bool
    {
        if (!$this->enabled) {
            return false;
        }
        return (bool)($this->flags[$flag] ?? false);
    }

    /**
     * Percentage rollout gate, stable per user id hash.
     */
    public function isEnabledForUser(string $flag, string|int $userId, int $percentage): bool
    {
        if (!$this->isEnabled($flag)) {
            return false;
        }
        $percentage = max(0, min(100, $percentage));
        // Stable hash 0..99
        $bucket = hexdec(substr(sha1($flag.'|'.$userId), 0, 8)) % 100;
        return $bucket < $percentage;
    }
}
