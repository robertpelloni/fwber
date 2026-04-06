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
}
