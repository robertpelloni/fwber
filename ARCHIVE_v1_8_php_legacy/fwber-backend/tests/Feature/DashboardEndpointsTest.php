<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_endpoint_is_available_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertOk();
        $response->assertJsonStructure([
            'total_matches',
            'pending_matches',
            'accepted_matches',
            'conversations',
            'profile_views',
            'today_views',
            'match_score_avg',
            'response_rate',
            'days_active',
            'last_login',
        ]);
    }

    public function test_dashboard_activity_endpoint_is_available_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard/activity?limit=8');

        $response->assertOk();
        $response->assertJsonIsArray();
    }

    public function test_dashboard_stats_endpoint_degrades_gracefully_when_match_table_is_missing(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('user_matches');

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertOk();
        $response->assertJsonPath('total_matches', 0);
        $response->assertJsonPath('accepted_matches', 0);
    }

    public function test_dashboard_activity_endpoint_degrades_gracefully_when_match_table_is_missing(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('user_matches');

        $response = $this->actingAs($user)->getJson('/api/dashboard/activity?limit=8');

        $response->assertOk();
        $response->assertJsonIsArray();
    }
}
