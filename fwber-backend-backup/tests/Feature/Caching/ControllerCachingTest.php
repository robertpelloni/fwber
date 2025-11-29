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
        config(['features.recommendations' => true]);
        
        $user = User::factory()->create();
        $user->profile()->create([
            "display_name" => "Test User",
            "date_of_birth" => "1990-01-01",
            "gender" => "male",
            "location_description" => "Test City",
        ]);
        $token = ApiToken::generateForUser($user, "test");

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
        config(['features.proximity_artifacts' => true]);

        $user = User::factory()->create();
        $user->profile()->create([
            "display_name" => "Test User",
            "date_of_birth" => "1990-01-01",
            "gender" => "male",
            "location_description" => "Test City",
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060
        ]);
        $token = ApiToken::generateForUser($user, "test");

        $expectedKeyPart = 'proximity:feed:lat:40.713:lng:-74.006';

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function($key) use ($expectedKeyPart) {
                return str_contains($key, $expectedKeyPart);
            })
            ->andReturn(collect([]));

        $this->withHeader("Authorization", "Bearer " . $token)
             ->getJson('/api/proximity/feed?lat=40.7128&lng=-74.0060&radius=1000');
    }

    public function test_matches_feed_is_cached_with_tags()
    {
        $user = User::factory()->create();
        $user->profile()->create([
            "display_name" => "Test User",
            "date_of_birth" => "1990-01-01",
            "gender" => "male",
            "location_description" => "Test City",
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
