<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;

class FailedJobControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create();
    }

    public function test_admin_can_list_failed_jobs()
    {
        // Insert a fake failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'TestJob']),
            'exception' => 'Test Exception',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/analytics/failed-jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at']
                ],
                'current_page',
                'total'
            ]);
            
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_admin_can_retry_failed_job()
    {
        $uuid = 'test-uuid-retry';
        
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'TestJob']),
            'exception' => 'Test Exception',
            'failed_at' => now(),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => $uuid]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/analytics/failed-jobs/{$uuid}/retry");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Job retry queued successfully']);
    }

    public function test_admin_can_delete_failed_job()
    {
        $uuid = 'test-uuid-delete';
        
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'TestJob']),
            'exception' => 'Test Exception',
            'failed_at' => now(),
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:forget', ['id' => $uuid]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/analytics/failed-jobs/{$uuid}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Job removed successfully']);
    }

    public function test_admin_can_retry_all_jobs()
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => 'all']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/analytics/failed-jobs/retry-all');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All failed jobs queued for retry']);
    }

    public function test_admin_can_flush_all_jobs()
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:flush');

        $response = $this->actingAs($this->admin)
            ->postJson('/api/analytics/failed-jobs/flush');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All failed jobs flushed']);
    }
}
