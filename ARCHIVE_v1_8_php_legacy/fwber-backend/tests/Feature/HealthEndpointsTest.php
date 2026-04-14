<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_application_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'version',
            'environment',
            'checks' => [
                'database',
                'redis',
                'cache',
                'storage',
                'queue',
                'broadcast',
            ],
            'metrics' => [
                'memory_usage',
                'memory_peak',
                'uptime',
            ],
        ]);
    }

    public function test_liveness_and_readiness_endpoints_are_available(): void
    {
        $this->getJson('/api/health/liveness')
            ->assertOk()
            ->assertJson(['status' => 'alive']);

        $this->getJson('/api/health/readiness')
            ->assertOk()
            ->assertJson(['status' => 'ready']);
    }

    public function test_metrics_endpoint_returns_structured_payload(): void
    {
        $response = $this->getJson('/api/health/metrics');

        $response->assertOk();
        $response->assertJsonStructure([
            'redis',
            'database',
        ]);
    }
}
