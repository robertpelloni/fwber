<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GeoSpoofDetection;
use App\Services\GeoSpoofDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoSpoofDetectionTest extends TestCase
{
    use RefreshDatabase;

    private GeoSpoofDetectionService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeoSpoofDetectionService::class);
        $this->user = User::factory()->create();
    }

    public function test_does_not_create_detection_for_localhost(): void
    {
        $detection = $this->service->detectSpoof(
            $this->user->id,
            37.7749, // San Francisco
            -122.4194,
            '127.0.0.1'
        );

        $this->assertNull($detection);
    }

    public function test_does_not_create_detection_for_low_suspicion(): void
    {
        // Mock IP geolocation to return nearby location
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'lat' => 37.7749,
                'lon' => -122.4194,
                'country' => 'USA',
                'city' => 'San Francisco',
                'isp' => 'Test ISP',
            ]),
        ]);

        $detection = $this->service->detectSpoof(
            $this->user->id,
            37.7749,
            -122.4194,
            '8.8.8.8'
        );

        $this->assertNull($detection);
    }

    public function test_creates_detection_for_high_distance_from_ip(): void
    {
        // Mock IP geolocation in New York
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'lat' => 40.7128, // New York
                'lon' => -74.0060,
                'country' => 'USA',
                'city' => 'New York',
                'isp' => 'Test ISP',
            ]),
        ]);

        // Claim to be in Los Angeles (2800 miles away)
        $detection = $this->service->detectSpoof(
            $this->user->id,
            34.0522, // Los Angeles
            -118.2437,
            '8.8.8.8'
        );

        $this->assertNotNull($detection);
        $this->assertGreaterThan(0, $detection->distance_km);
        $this->assertGreaterThan(25, $detection->suspicion_score);
        $this->assertContains('ip_distance_extreme', $detection->detection_flags);
    }

    public function test_detects_impossible_velocity(): void
    {
        // Create first detection in San Francisco with all required fields
        // Important: This represents a PAST location check
        $first = GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '1.2.3.4',
            'latitude' => 37.7749, // SF
            'longitude' => -122.4194,
            'ip_latitude' => 37.7749,
            'ip_longitude' => -122.4194,
            'distance_km' => 0,
            'velocity_kmh' => null,
            'suspicion_score' => 30,
            'detection_flags' => ['some_flag'],
            'is_confirmed_spoof' => false,
            'detected_at' => now()->subHour(), // 1 hour ago
        ]);

        // Mock IP geolocation - IP location matches claimed location to isolate velocity test
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'lat' => 40.7128, // NY - matches claimed location
                'lon' => -74.0060,
                'country' => 'USA',
                'city' => 'New York',
                'isp' => 'Test ISP',
            ]),
        ]);

        // User was in SF 1 hour ago, now claims NY (~4000km)
        // Velocity = 4000km / 1hr = 4000 km/h (impossible!)
        // Even with 0 IP distance score, velocity alone should trigger: +50 score >= 25 threshold
        $detection = $this->service->detectSpoof(
            $this->user->id,
            40.7128, // NY claimed location
            -74.0060,
            '8.8.8.8'
        );

        // Debug output if needed
        if (!$detection) {
            $this->fail('Detection was null. Check that velocity calculation triggers >= 25 suspicion score.');
        }

        $this->assertNotNull($detection);
        $this->assertNotNull($detection->velocity_kmh, 'Velocity should be calculated');
        $this->assertGreaterThan(1000, $detection->velocity_kmh, 'SF to NY in 1 hour should be ~4000 km/h');
        $this->assertContains('impossible_velocity', $detection->detection_flags);
        $this->assertGreaterThanOrEqual(50, $detection->suspicion_score, 'Impossible velocity adds +50 score');
    }

    public function test_high_risk_scope_filters_correctly(): void
    {
        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 80,
            'detection_flags' => ['ip_distance_extreme'],
            'detected_at' => now(),
        ]);

        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 40,
            'detection_flags' => ['ip_distance_moderate'],
            'detected_at' => now(),
        ]);

        $highRisk = GeoSpoofDetection::highRisk()->count();

        $this->assertEquals(1, $highRisk);
    }

    public function test_unconfirmed_scope_filters_correctly(): void
    {
        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 80,
            'detection_flags' => [],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ]);

        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 85,
            'detection_flags' => [],
            'is_confirmed_spoof' => true,
            'detected_at' => now(),
        ]);

        $unconfirmed = GeoSpoofDetection::unconfirmed()->count();

        $this->assertEquals(1, $unconfirmed);
    }

    public function test_is_high_risk_method(): void
    {
        $highRisk = GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 75,
            'detection_flags' => [],
            'detected_at' => now(),
        ]);

        $lowRisk = GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 50,
            'detection_flags' => [],
            'detected_at' => now(),
        ]);

        $this->assertTrue($highRisk->isHighRisk());
        $this->assertFalse($lowRisk->isHighRisk());
    }

    public function test_get_user_spoof_stats(): void
    {
        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 80,
            'detection_flags' => [],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ]);

        GeoSpoofDetection::create([
            'user_id' => $this->user->id,
            'ip_address' => '8.8.8.8',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'suspicion_score' => 90,
            'detection_flags' => [],
            'is_confirmed_spoof' => true,
            'detected_at' => now(),
        ]);

        $stats = $this->service->getUserSpoofStats($this->user->id);

        $this->assertEquals(2, $stats['total_detections']);
        $this->assertEquals(2, $stats['high_risk_detections']);
        $this->assertEquals(1, $stats['confirmed_spoofs']);
        $this->assertTrue($stats['is_high_risk_user']);
    }
}
