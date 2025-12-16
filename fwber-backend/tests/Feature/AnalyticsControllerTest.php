<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use App\Services\WebSocketService;
use Illuminate\Support\Facades\Cache;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_index_returns_real_performance_metrics(): void
    {
        $user = User::factory()->create();
        
        // Clear cache to ensure we hit the controller logic
        Cache::forget('analytics_7d');

        // Mock Redis
        Redis::shouldReceive('info')
            ->with('stats')
            ->andReturn([
                'keyspace_hits' => 80,
                'keyspace_misses' => 20,
            ]);
            
        Redis::shouldReceive('get')
            ->with('apm:requests:' . now()->format('Y-m-d'))
            ->andReturn(100);
            
        Redis::shouldReceive('get')
            ->with('apm:errors:' . now()->format('Y-m-d'))
            ->andReturn(5);

        // Mock WebSocketService
        $this->mock(WebSocketService::class, function ($mock) {
            $mock->shouldReceive('getOnlineUsers')
                ->andReturn(['user1', 'user2', 'user3']);
        });

        $response = $this->actingAs($user)->getJson('/api/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'performance' => [
                    'api_response_time',
                    'slow_requests_24h',
                    'sse_connections',
                    'cache_hit_rate',
                    'error_rate',
                ]
            ]);
            
        $data = $response->json('performance');
        
        $this->assertEquals(3, $data['sse_connections']);
        $this->assertEquals(80.0, $data['cache_hit_rate']); // 80 / (80+20) * 100
        $this->assertEquals(5.0, $data['error_rate']); // 5 / 100 * 100
    }
}
