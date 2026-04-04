<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
