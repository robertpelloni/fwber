<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicWebRoutesTest extends TestCase
{
    public function test_root_route_returns_backend_status_payload(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertJsonStructure([
            'service',
            'status',
            'environment',
            'up',
            'health',
            'nodeinfo',
        ]);
        $response->assertJsonPath('service', 'fwber-backend');
    }

    public function test_webfinger_requires_resource_query_parameter(): void
    {
        $response = $this->get('/.well-known/webfinger');

        $response->assertStatus(400);
        $response->assertHeader('Content-Type', 'application/jrd+json');
    }

    public function test_nodeinfo_discovery_endpoint_returns_link_payload(): void
    {
        $response = $this->get('/.well-known/nodeinfo');

        $response->assertOk();
        $response->assertJsonStructure([
            'links' => [
                ['rel', 'href'],
            ],
        ]);
    }

    public function test_nodeinfo_schema_endpoint_degrades_gracefully_on_minimal_schema(): void
    {
        $response = $this->get('/nodeinfo/2.0');

        $response->assertOk();
        $response->assertJsonPath('version', '2.0');
        $response->assertJsonStructure([
            'software' => ['name', 'version'],
            'usage' => [
                'users' => ['total', 'activeHalfyear', 'activeMonth'],
            ],
        ]);
    }
}
