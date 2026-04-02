<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Venue;
use App\Models\VenueCheckin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VenueRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $db = DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
            $db->sqliteCreateFunction('radians', 'deg2rad', 1);
            $db->sqliteCreateFunction('sqrt', 'sqrt', 1);
            $db->sqliteCreateFunction('atan2', 'atan2', 2);
        }
    }

    public function test_venues_include_ranking_strategy_metadata(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Venue::factory()->create([
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/venues?lat=42.3314&lng=-83.0458&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('meta.ranking_strategy.trusted_visitors', true)
            ->assertJsonPath('meta.ranking_strategy.scene_alignment', true)
            ->assertJsonPath('meta.ranking_strategy.venue_health', true)
            ->assertJsonPath('meta.ranking_strategy.freshness', true)
            ->assertJsonPath('meta.ranking_strategy.distance', true)
            ->assertJsonCount(1, 'venues');
    }

    public function test_trusted_scene_aligned_venue_can_outrank_a_closer_stranger_venue(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
            'interests' => ['coffee', 'nightlife'],
        ]);
        UserProfile::factory()->create([
            'user_id' => $friend->id,
        ]);
        UserProfile::factory()->create([
            'user_id' => $stranger->id,
        ]);

        $topic = Topic::query()->firstOrCreate(
            ['slug' => 'warehouse-nights'],
            [
                'label' => 'Warehouse Nights',
                'description' => 'Late nights and underground rooms.',
                'emoji' => '🌃',
                'category' => 'culture',
                'aliases' => ['warehouse', 'nightlife'],
                'is_featured' => true,
                'sort_order' => 99,
            ]
        );
        $viewer->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        Friend::factory()->create([
            'user_id' => $viewer->id,
            'friend_id' => $friend->id,
            'status' => 'accepted',
        ]);
        Friend::factory()->create([
            'user_id' => $friend->id,
            'friend_id' => $viewer->id,
            'status' => 'accepted',
        ]);

        $closerVenue = Venue::factory()->create([
            'name' => 'Generic Nearby Lounge',
            'description' => 'General local hangout.',
            'business_type' => 'bar',
            'latitude' => 42.33145,
            'longitude' => -83.04575,
            'verification_status' => 'pending',
            'max_capacity' => 80,
        ]);
        VenueCheckin::create([
            'user_id' => $stranger->id,
            'venue_id' => $closerVenue->id,
            'message' => 'Just hanging out nearby.',
            'created_at' => now()->subMinutes(10),
        ]);

        $trustedVenue = Venue::factory()->create([
            'name' => 'Warehouse Coffee Collective',
            'description' => 'Coffee before the warehouse nightlife starts.',
            'business_type' => 'cafe',
            'latitude' => 42.3328,
            'longitude' => -83.0474,
            'verification_status' => 'verified',
            'max_capacity' => 220,
        ]);
        VenueCheckin::create([
            'user_id' => $friend->id,
            'venue_id' => $trustedVenue->id,
            'message' => 'Warehouse coffee meetup before nightlife.',
            'created_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/venues?lat=42.3314&lng=-83.0458&radius=1000&ranking_strategy=trust-aware');

        $response->assertOk()
            ->assertJsonPath('venues.0.name', 'Warehouse Coffee Collective')
            ->assertJsonPath('venues.0.scene_signals.matched_topics.0.slug', 'warehouse-nights');
    }
}
