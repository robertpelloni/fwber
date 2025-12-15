<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use App\Models\User;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a dummy log file
        if (!File::exists(storage_path('logs'))) {
            File::makeDirectory(storage_path('logs'));
        }
        File::put(storage_path('logs/test.log'), "Line 1\nLine 2\nLine 3");
    }

    protected function tearDown(): void
    {
        // Clean up
        if (File::exists(storage_path('logs/test.log'))) {
            File::delete(storage_path('logs/test.log'));
        }
        parent::tearDown();
    }

    public function test_admin_can_list_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/admin/logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['name', 'size', 'updated_at']
            ]);
    }

    public function test_admin_can_view_log_content()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/admin/logs/test.log');

        $response->assertStatus(200)
            ->assertJson([
                'filename' => 'test.log',
                'content' => "Line 1\nLine 2\nLine 3"
            ]);
    }

    public function test_returns_404_for_non_existent_log()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/admin/logs/non_existent.log');

        $response->assertStatus(404);
    }
}
