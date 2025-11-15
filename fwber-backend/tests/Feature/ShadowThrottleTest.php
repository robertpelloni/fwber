<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShadowThrottle;
use App\Services\ShadowThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShadowThrottleTest extends TestCase
{
    use RefreshDatabase;

    private ShadowThrottleService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShadowThrottleService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_apply_throttle_to_user(): void
    {
        $throttle = $this->service->applyThrottle(
            $this->user->id,
            'spam',
            2,
            24,
            null,
            'Test throttle'
        );

        $this->assertInstanceOf(ShadowThrottle::class, $throttle);
        $this->assertEquals($this->user->id, $throttle->user_id);
        $this->assertEquals('spam', $throttle->reason);
        $this->assertEquals(2, $throttle->severity);
        $this->assertEquals(0.50, $throttle->visibility_reduction);
        $this->assertNotNull($throttle->expires_at);
    }

    public function test_visibility_reduction_scales_with_severity(): void
    {
        $severityToVisibility = [
            1 => 0.70,
            2 => 0.50,
            3 => 0.30,
            4 => 0.15,
            5 => 0.05,
        ];

        foreach ($severityToVisibility as $severity => $expectedVisibility) {
            $throttle = $this->service->applyThrottle(
                $this->user->id,
                'spam', // Use valid enum value
                $severity,
                24
            );

            $this->assertEquals(
                $expectedVisibility,
                (float) $throttle->visibility_reduction,
                "Severity {$severity} should have visibility {$expectedVisibility}"
            );

            $throttle->delete();
        }
    }

    public function test_can_get_active_throttle_for_user(): void
    {
        $throttle = $this->service->applyThrottle($this->user->id, 'spam', 2, 24);

        $active = $this->service->getActiveThrottle($this->user->id);

        $this->assertNotNull($active);
        $this->assertEquals($throttle->id, $active->id);
    }

    public function test_expired_throttles_are_not_active(): void
    {
        $throttle = ShadowThrottle::create([
            'user_id' => $this->user->id,
            'reason' => 'spam',
            'severity' => 2,
            'visibility_reduction' => 0.50,
            'started_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        $active = $this->service->getActiveThrottle($this->user->id);

        $this->assertNull($active);
    }

    public function test_future_throttles_are_not_active(): void
    {
        $throttle = ShadowThrottle::create([
            'user_id' => $this->user->id,
            'reason' => 'spam',
            'severity' => 2,
            'visibility_reduction' => 0.50,
            'started_at' => now()->addHour(),
            'expires_at' => now()->addDays(2),
        ]);

        $active = $this->service->getActiveThrottle($this->user->id);

        $this->assertNull($active);
    }

    public function test_permanent_throttles_have_no_expiry(): void
    {
        $throttle = $this->service->applyThrottle(
            $this->user->id,
            'manual', // Use valid enum value
            5,
            null // No expiry
        );

        $this->assertNull($throttle->expires_at);
        $this->assertTrue($throttle->isActive());
    }

    public function test_visibility_multiplier_returns_1_for_unthrottled_user(): void
    {
        $multiplier = $this->service->getVisibilityMultiplier($this->user->id);

        $this->assertEquals(1.0, $multiplier);
    }

    public function test_visibility_multiplier_returns_reduction_for_throttled_user(): void
    {
        $this->service->applyThrottle($this->user->id, 'spam', 3, 24);

        $multiplier = $this->service->getVisibilityMultiplier($this->user->id);

        $this->assertEquals(0.30, $multiplier);
    }

    public function test_is_throttled_returns_correct_status(): void
    {
        $this->assertFalse($this->service->isThrottled($this->user->id));

        $this->service->applyThrottle($this->user->id, 'spam', 2, 24);

        $this->assertTrue($this->service->isThrottled($this->user->id));
    }

    public function test_can_remove_throttle(): void
    {
        $this->service->applyThrottle($this->user->id, 'spam', 2, 24);
        $this->assertTrue($this->service->isThrottled($this->user->id));

        $count = $this->service->removeThrottle($this->user->id);

        $this->assertEquals(1, $count);
        $this->assertFalse($this->service->isThrottled($this->user->id));
    }

    public function test_auto_throttle_applies_progressive_severity_for_flags(): void
    {
        // 3 flags = severity 2
        $throttle = $this->service->applyAutoThrottleForFlags($this->user->id, 3);
        $this->assertEquals(2, $throttle->severity);
        $this->assertEquals(24, round(now()->diffInHours($throttle->expires_at)));

        $throttle->delete();

        // 5 flags = severity 3
        $throttle = $this->service->applyAutoThrottleForFlags($this->user->id, 5);
        $this->assertEquals(3, $throttle->severity);
        $this->assertEquals(72, round(now()->diffInHours($throttle->expires_at)));

        $throttle->delete();

        // 10 flags = severity 4
        $throttle = $this->service->applyAutoThrottleForFlags($this->user->id, 10);
        $this->assertEquals(4, $throttle->severity);
        $this->assertEquals(168, round(now()->diffInHours($throttle->expires_at)));
    }

    public function test_auto_throttle_for_spam_scales_with_post_count(): void
    {
        $throttle = $this->service->applySpamThrottle($this->user->id, 25);

        $this->assertEquals('rapid_posting', $throttle->reason);
        $this->assertEquals(3, $throttle->severity); // ceil(25/10) = 3
        $this->assertNotNull($throttle->expires_at);
    }

    public function test_prune_expired_removes_old_throttles(): void
    {
        // Create expired throttle
        ShadowThrottle::create([
            'user_id' => $this->user->id,
            'reason' => 'spam',
            'severity' => 2,
            'visibility_reduction' => 0.50,
            'started_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        // Create active throttle
        ShadowThrottle::create([
            'user_id' => $this->user->id,
            'reason' => 'spam',
            'severity' => 1,
            'visibility_reduction' => 0.70,
            'started_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertEquals(2, ShadowThrottle::count());

        $pruned = $this->service->pruneExpired();

        $this->assertEquals(1, $pruned);
        $this->assertEquals(1, ShadowThrottle::count());
    }

    public function test_get_user_throttle_stats(): void
    {
        $this->service->applyThrottle($this->user->id, 'spam', 2, 24);
        
        // Create an expired one
        ShadowThrottle::create([
            'user_id' => $this->user->id,
            'reason' => 'spam', // Use valid enum value
            'severity' => 1,
            'visibility_reduction' => 0.70,
            'started_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        $stats = $this->service->getUserThrottleStats($this->user->id);

        $this->assertEquals(2, $stats['total_throttles']);
        $this->assertEquals(1, $stats['active_throttles']);
        $this->assertEquals(0.50, $stats['current_visibility']);
        $this->assertTrue($stats['is_throttled']);
    }

    public function test_highest_severity_throttle_takes_precedence(): void
    {
        $this->service->applyThrottle($this->user->id, 'spam', 2, 24);
        $this->service->applyThrottle($this->user->id, 'flagged_content', 4, 48);

        $active = $this->service->getActiveThrottle($this->user->id);

        $this->assertEquals(4, $active->severity);
        $this->assertEquals(0.15, (float) $active->visibility_reduction);
    }
}
