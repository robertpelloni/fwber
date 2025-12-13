<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyActiveUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AnalyticsRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_retention_correctly()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Create a cohort in Month -2
        $monthMinus2 = now()->subMonths(2)->startOfMonth();
        $user1 = User::factory()->create(['created_at' => $monthMinus2->copy()->addDay()]);
        $user2 = User::factory()->create(['created_at' => $monthMinus2->copy()->addDay()]);

        // User 1 active in Month -1
        DailyActiveUser::create([
            'user_id' => $user1->id,
            'date' => $monthMinus2->copy()->addMonth()->addDay(),
        ]);

        // User 1 and 2 active in Month 0 (Current)
        DailyActiveUser::create([
            'user_id' => $user1->id,
            'date' => now(),
        ]);
        DailyActiveUser::create([
            'user_id' => $user2->id,
            'date' => now(),
        ]);

        $response = $this->getJson('/api/analytics/retention');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Find the cohort for Month -2
        $cohortKey = $monthMinus2->format('Y-m');
        $cohort = collect($data)->firstWhere('month', $cohortKey);

        $this->assertNotNull($cohort, "Cohort for $cohortKey not found");
        $this->assertEquals(2, $cohort['size']);

        // Check Month 1 retention (Month -1) -> 50% (1/2)
        $month1 = collect($cohort['retention'])->firstWhere('month_offset', 1);
        $this->assertNotNull($month1, "Month 1 retention not found");
        $this->assertEquals(50, $month1['percentage']);

        // Check Month 2 retention (Month 0) -> 100% (2/2)
        $month2 = collect($cohort['retention'])->firstWhere('month_offset', 2);
        $this->assertNotNull($month2, "Month 2 retention not found");
        $this->assertEquals(100, $month2['percentage']);
    }
}
