<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\SlowRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class SlowRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_apm_middleware_logs_slow_requests_with_metrics(): void
    {
        // Enable APM and set low threshold
        Config::set('apm.enabled', true);
        Config::set('apm.slow_request_threshold', 0); // Log everything

        // Define a slow route
        Route::get('/test-slow-route', function () {
            usleep(10000); // 10ms
            return 'ok';
        })->middleware(\App\Http\Middleware\ApmMiddleware::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/test-slow-route');

        $response->assertStatus(200);

        $this->assertDatabaseHas('slow_requests', [
            'user_id' => $user->id,
            'url' => url('/test-slow-route'),
            'method' => 'GET',
        ]);

        $slowRequest = SlowRequest::first();
        $this->assertNotNull($slowRequest->db_query_count);
        $this->assertNotNull($slowRequest->memory_usage_kb);
    }

    public function test_analytics_endpoint_returns_slow_requests(): void
    {
        $user = User::factory()->create();
        
        // Create some slow requests
        SlowRequest::create([
            'user_id' => $user->id,
            'method' => 'GET',
            'url' => 'http://localhost/api/slow',
            'duration_ms' => 1500,
            'ip' => '127.0.0.1',
            'db_query_count' => 5,
            'memory_usage_kb' => 2048,
        ]);

        $response = $this->actingAs($user)->getJson('/api/analytics/slow-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'method',
                    'url',
                    'duration_ms',
                    'db_query_count',
                    'memory_usage_kb',
                    'created_at',
                ]
            ]);
    }

    public function test_analytics_stats_endpoint_returns_metrics(): void
    {
        $user = User::factory()->create();
        
        // Create some slow requests
        SlowRequest::create([
            'user_id' => $user->id,
            'method' => 'GET',
            'url' => 'http://localhost/api/slow',
            'route_name' => 'api.slow',
            'action' => 'SlowController@index',
            'duration_ms' => 1500,
            'ip' => '127.0.0.1',
            'db_query_count' => 10,
            'memory_usage_kb' => 5000,
        ]);

        $response = $this->actingAs($user)->getJson('/api/analytics/slow-requests/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'endpoint',
                    'method',
                    'count',
                    'avg_duration',
                    'max_duration',
                    'avg_queries',
                    'avg_memory',
                ]
            ]);
    }
}
