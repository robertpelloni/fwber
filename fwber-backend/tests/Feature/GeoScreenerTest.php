<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Services\GeoScreenerClient;
use App\Models\User;

class GeoScreenerTest extends TestCase
{
    public function test_client_honors_enabled_setting()
    {
        config(['services.geo_screener.enabled' => true]);
        $this->assertTrue(app(GeoScreenerClient::class)->isEnabled());

        config(['services.geo_screener.enabled' => false]);
        $this->assertFalse(app(GeoScreenerClient::class)->isEnabled());
    }

    public function test_index_location_sends_correct_payload()
    {
        config([
            'services.geo_screener.enabled' => true,
            'services.geo_screener.url' => 'http://mock-geo:8081'
        ]);

        Http::fake([
            'http://mock-geo:8081/index' => Http::response(['status' => 'indexed'], 200)
        ]);

        $client = app(GeoScreenerClient::class);
        $result = $client->indexLocation(123, 40.7128, -74.0060);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://mock-geo:8081/index' &&
                   $request['user_id'] === 123 &&
                   $request['lat'] === 40.7128 &&
                   $request['lng'] === -74.0060;
        });
    }

    public function test_get_nearby_users_returns_array()
    {
        config([
            'services.geo_screener.enabled' => true,
            'services.geo_screener.url' => 'http://mock-geo:8081'
        ]);

        Http::fake([
            'http://mock-geo:8081/nearby*' => Http::response(['users' => [1, 2, 3]], 200)
        ]);

        $client = app(GeoScreenerClient::class);
        $users = $client->getNearbyUsers(40.7128, -74.0060, 500);

        $this->assertEquals([1, 2, 3], $users);
    }
}