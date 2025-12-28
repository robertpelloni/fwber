<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use App\Models\SlowRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApmMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('apm.enabled', true);
        Config::set('apm.slow_request_threshold', 1000);
    }

    public function test_it_logs_slow_requests()
    {
        // Define a slow route
        Route::get('/test-slow-route', function () {
            usleep(1100000); // Sleep for 1.1 seconds
            return 'slow response';
        })->middleware(\App\Http\Middleware\ApmMiddleware::class);

        // Make the request
        $response = $this->get('/test-slow-route');

        $response->assertStatus(200);

        // Assert it was logged
        $this->assertDatabaseHas('slow_requests', [
            'url' => 'http://localhost:8000/test-slow-route',
            'method' => 'GET',
        ]);

        $log = SlowRequest::where('url', 'http://localhost:8000/test-slow-route')->first();
        $this->assertGreaterThan(1000, $log->duration_ms);
    }

    public function test_it_does_not_log_fast_requests()
    {
        // Define a fast route
        Route::get('/test-fast-route', function () {
            return 'fast response';
        })->middleware(\App\Http\Middleware\ApmMiddleware::class);

        // Make the request
        $response = $this->get('/test-fast-route');

        $response->assertStatus(200);

        // Assert it was NOT logged
        $this->assertDatabaseMissing('slow_requests', [
            'url' => 'http://localhost/test-fast-route',
        ]);
    }
}
