<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_another_user(): void
    {
        $user1 = User::factory()->create();
        $user1->profile()->create([
            'display_name' => 'User One',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'Test City',
        ]);

        $user2 = User::factory()->create();
        $user2->profile()->create([
            'display_name' => 'User Two',
            'date_of_birth' => '1992-01-01',
            'gender' => 'female',
            'location_description' => 'Test City',
        ]);

        $token = ApiToken::generateForUser($user1, 'test');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $user2->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'action' => 'like',
                'is_match' => false,
                'message' => 'Action recorded',
            ]);

        $this->assertDatabaseHas('match_actions', [
            'user_id' => $user1->id,
            'target_user_id' => $user2->id,
            'action' => 'like',
        ]);
    }

    public function test_mutual_like_creates_match(): void
    {
        $this->mock(EmailNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendNewMatchNotification')->twice();
        });

        $user1 = User::factory()->create();
        $user1->profile()->create([
            'display_name' => 'User One',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'Test City',
        ]);

        $user2 = User::factory()->create();
        $user2->profile()->create([
            'display_name' => 'User Two',
            'date_of_birth' => '1992-01-01',
            'gender' => 'female',
            'location_description' => 'Test City',
        ]);

        // User 1 likes User 2
        DB::table('match_actions')->insert([
            'user_id' => $user1->id,
            'target_user_id' => $user2->id,
            'action' => 'like',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = ApiToken::generateForUser($user2, 'test');

        // User 2 likes User 1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $user1->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'action' => 'like',
                'is_match' => true,
                'message' => "It's a match!",
            ]);

        $this->assertDatabaseHas('matches', [
            'user1_id' => min($user1->id, $user2->id),
            'user2_id' => max($user1->id, $user2->id),
        ]);
    }

    public function test_established_matches_endpoint(): void
    {
        $user1 = User::factory()->create();
        $user1->profile()->create([
            'display_name' => 'User One',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'Test City',
        ]);

        $user2 = User::factory()->create();
        $user2->profile()->create([
            'display_name' => 'User Two',
            'date_of_birth' => '1992-01-01',
            'gender' => 'female',
            'location_description' => 'Test City',
        ]);

        // Create match directly
        DB::table('matches')->insert([
            'user1_id' => min($user1->id, $user2->id),
            'user2_id' => max($user1->id, $user2->id),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = ApiToken::generateForUser($user1, 'test');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/matches/established');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user1_id',
                        'user2_id',
                        'other_user' => [
                            'id',
                            'name',
                            // ... other user fields
                        ],
                    ],
                ],
            ]);
            
        // Verify user2 is in the list
        $data = $response->json('data');
        $this->assertEquals($user2->id, $data[0]['other_user']['id']);
    }
}
