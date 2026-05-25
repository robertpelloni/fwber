<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_data()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile/export');
        $response->dump();

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'profile',
                    'photos',
                    'matches_as_user1',
                    'matches_as_user2',
                    'sent_messages',
                    'received_messages',
                    'groups',
                    'events',
                    'subscriptions',
                    'gifts_received',
                    'gifts_sent',
                ],
                'generated_at',
            ]);
    }

    public function test_unauthenticated_user_cannot_export_data()
    {
        $response = $this->getJson('/api/profile/export');

        $response->assertStatus(401);
    }
}
