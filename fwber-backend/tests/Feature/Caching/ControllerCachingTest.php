<?php

namespace Tests\Feature\Caching;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ControllerCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendations_are_cached()
    {
        $this->withoutMiddleware(\App\Http\Middleware\TrackUserActivity::class);
        config(['features.recommendations' => true]);
        
        $user = User::factory()->create(['name' => 'Test User']);
        $user->profile()->create([
            "birthdate" => "1990-01-01",
            "gender" => "male",
            "location_name" => "Test City",
        ]);
        $token = ApiToken::generateForUser($user, "test");

        Cache::shouldReceive('tags')
            ->once()
            ->with(["recommendations:user:{$user->id}"])
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function($key, $ttl, $callback) use ($user) {
                return str_starts_with($key, "recommendations:user:{$user->id}");
            })
            ->andReturn([]);

        $this->withHeader("Authorization", "Bearer " . $token)
             ->getJson('/api/recommendations');
    }

    public function test_proximity_artifacts_are_cached()
    {
        $this->withoutMiddleware(\App\Http\Middleware\TrackUserActivity::class);
        config(['features.proximity_artifacts' => true]);

        $user = User::factory()->create(['name' => 'Test User']);
        $user->profile()->create([
            "birthdate" => "1990-01-01",
            "gender" => "male",
            "location_name" => "Test City",
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ]);
        $token = ApiToken::generateForUser($user, "test");

        $expectedKeyPart = 'proximity:feed:lat:40.713:lng:-74.006';

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function($key) use ($expectedKeyPart) {
                return str_contains($key, $expectedKeyPart);
            })
            ->andReturn(collect([]));

        Cache::shouldReceive('has')
            ->andReturn(true);

        $this->withHeader("Authorization", "Bearer " . $token)
             ->getJson('/api/proximity/feed?lat=40.7128&lng=-74.0060&radius=1000');
    }

    public function test_matches_feed_is_cached_with_tags()
    {
        $this->withoutMiddleware(\App\Http\Middleware\TrackUserActivity::class);
        config(['telemetry.enabled' => false]);
        
        $user = User::factory()->create(['name' => 'Test User']);
        $user->profile()->create([
            "birthdate" => "1990-01-01",
            "gender" => "male",
            "location_name" => "Test City",
        ]);
        $token = ApiToken::generateForUser($user, "test");

        Cache::shouldReceive('tags')
            ->once()
            ->with(["matches_feed:user_{$user->id}"])
            ->andReturnSelf();
            
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));

        $this->withHeader("Authorization", "Bearer " . $token)
             ->getJson('/api/matches');
    }
}
