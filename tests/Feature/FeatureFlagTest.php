<?php

namespace Tests\Feature;

use App\Services\FeatureFlagService;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_flags_respect_defaults_and_env(): void
    {
        // Defaults from config
        $service = app(FeatureFlagService::class);
        $this->assertTrue($service->enabled());
        $this->assertTrue($service->isEnabled('onboarding_v1'));
        $this->assertFalse($service->isEnabled('messaging_ws'));
    }

    public function test_percentage_rollout_is_stable(): void
    {
        $service = app(FeatureFlagService::class);
        $uid = 12345;
        $flag = 'matching_feed_v1';

        $a = $service->isEnabledForUser($flag, $uid, 25);
        $b = $service->isEnabledForUser($flag, $uid, 25);
        $this->assertSame($a, $b, 'Rollout decision must be stable for same user');

        $low = $service->isEnabledForUser($flag, $uid, 0);
        $high = $service->isEnabledForUser($flag, $uid, 100);
        $this->assertFalse($low);
        $this->assertTrue($high);
    }
}
