<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use App\Models\SlowRequest;

class ApmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable APM and set threshold to 100ms for testing
        Config::set('apm.enabled', true);
        Config::set('apm.slow_request_threshold', 100);
    }

    public function test_it_logs_slow_requests()
    {
        // Define a slow route
        Route::get('test/slow', function () {
            usleep(200000); // 200ms
            return 'slow';
        })->middleware(\App\Http\Middleware\ApmMiddleware::class);

        // Hit the route
        $response = $this->get('test/slow');

        $response->assertStatus(200);

        // Assert it was logged
        $this->assertDatabaseHas('slow_requests', [
            'url' => 'http://localhost/test/slow',
            'method' => 'GET',
        ]);
        
        // Verify duration is recorded
        $log = SlowRequest::where('url', 'http://localhost/test/slow')->first();
        $this->assertNotNull($log);
        $this->assertGreaterThan(100, $log->duration_ms);
    }

    public function test_it_does_not_log_fast_requests()
    {
        // Define a fast route
        Route::get('test/fast', function () {
            return 'fast';
        })->middleware(\App\Http\Middleware\ApmMiddleware::class);

        // Hit the route
        $response = $this->get('test/fast');

        $response->assertStatus(200);

        // Assert it was NOT logged
        $this->assertDatabaseMissing('slow_requests', [
            'url' => 'http://localhost/test/fast',
        ]);
    }
}
