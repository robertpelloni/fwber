<?php

namespace Tests\Feature;

use App\Models\UserMatch as MatchModel;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DatePlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/wingman/date-ideas/1');
        $response->assertStatus(401);
    }

    public function test_rejects_unmatched_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // No match record exists

        $response = $this->actingAs($user1)->getJson("/api/wingman/date-ideas/{$user2->id}");
        
        $response->assertStatus(403)
                 ->assertJson(['message' => 'You are not matched with this user.']);
    }

    public function test_generates_ideas_for_matched_users()
    {
        $user1 = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user1->id, 'bio' => 'Likes coffee and dogs']);
        
        $user2 = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user2->id, 'bio' => 'Enjoys long walks and lattes']);

        MatchModel::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
            'status' => 'accepted'
        ]);

        // Mock OpenAI HTTP response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'ideas' => [
                                    [
                                        'title' => 'Dog Park & Cafe',
                                        'description' => 'A great afternoon out.',
                                        'reason' => 'You both like dogs and coffee.',
                                        'venue' => 'Central Bark',
                                        'estimated_cost' => '$15',
                                        'duration' => '2 hours',
                                        'conversation_starter' => 'What is your favorite dog breed?'
                                    ]
                                ]
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($user1)->getJson("/api/wingman/date-ideas/{$user2->id}?location=Downtown");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'ideas' => [
                         '*' => [
                             'title',
                             'description',
                             'reason',
                             'venue',
                             'estimated_cost',
                             'duration',
                             'conversation_starter'
                         ]
                     ]
                 ]);
                 
        $this->assertEquals('Dog Park & Cafe', $response->json('ideas.0.title'));
    }
}
