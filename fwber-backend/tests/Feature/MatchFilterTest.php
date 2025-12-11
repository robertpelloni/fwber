<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_matches_by_smoking_status()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        // Create a matching user
        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'smoking_status' => 'non-smoker',
        ]);

        // Create a non-matching user
        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'smoking_status' => 'smoker',
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?smoking=non-smoker');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_drinking_status()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'drinking_status' => 'social',
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'drinking_status' => 'never',
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?drinking=social');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_body_type()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'body_type' => 'athletic',
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'body_type' => 'average',
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?body_type=athletic');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_min_height()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'height_cm' => 180,
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'height_cm' => 160,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?height_min=170');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_has_bio()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'bio' => 'I have a bio',
        ]);

        $nonMatch = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch->id,
            'bio' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?has_bio=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_filter_matches_by_verified_only()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create(['email_verified_at' => now()]);
        UserProfile::factory()->create(['user_id' => $match->id]);

        $nonMatch = User::factory()->unverified()->create();
        UserProfile::factory()->create(['user_id' => $nonMatch->id]);

        $response = $this->actingAs($user)->getJson('/api/matches?verified_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }

    public function test_can_combine_filters()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $match = User::factory()->create(['email_verified_at' => now()]);
        UserProfile::factory()->create([
            'user_id' => $match->id,
            'smoking_status' => 'non-smoker',
            'height_cm' => 180,
        ]);

        // Wrong smoking status
        $nonMatch1 = User::factory()->create(['email_verified_at' => now()]);
        UserProfile::factory()->create([
            'user_id' => $nonMatch1->id,
            'smoking_status' => 'smoker',
            'height_cm' => 180,
        ]);

        // Too short
        $nonMatch2 = User::factory()->create(['email_verified_at' => now()]);
        UserProfile::factory()->create([
            'user_id' => $nonMatch2->id,
            'smoking_status' => 'non-smoker',
            'height_cm' => 160,
        ]);

        // Not verified
        $nonMatch3 = User::factory()->unverified()->create();
        UserProfile::factory()->create([
            'user_id' => $nonMatch3->id,
            'smoking_status' => 'non-smoker',
            'height_cm' => 180,
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches?smoking=non-smoker&height_min=170&verified_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $match->id);
    }
}
