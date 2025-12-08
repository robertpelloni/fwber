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

    public function test_apm_middleware_logs_slow_requests(): void
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
                    'created_at',
                ]
            ]);
    }
}
