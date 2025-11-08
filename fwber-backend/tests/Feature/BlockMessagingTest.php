<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Block;
use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_blocked_users_cannot_send_messages()
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceHeader = $this->apiHeaderFor($alice);
        $bobHeader = $this->apiHeaderFor($bob);

        // Simulate match creation (minimal stub)
        \DB::table('matches')->insert([
            'user1_id' => $alice->id,
            'user2_id' => $bob->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Alice blocks Bob
        Block::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        $this->withHeaders($bobHeader)
            ->postJson('/api/messages', [
                'receiver_id' => $alice->id,
                'content' => 'Hello Alice'
            ])
            ->assertStatus(403)
            ->assertJson(['error' => 'Messaging blocked between users']);
    }

    public function test_blocked_users_cannot_view_conversation()
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceHeader = $this->apiHeaderFor($alice);
        $bobHeader = $this->apiHeaderFor($bob);

        \DB::table('matches')->insert([
            'user1_id' => $alice->id,
            'user2_id' => $bob->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Block::create(['blocker_id' => $bob->id, 'blocked_id' => $alice->id]);

        $this->withHeaders($aliceHeader)
            ->getJson('/api/messages/' . $bob->id)
            ->assertStatus(403)
            ->assertJson(['error' => 'Conversation access blocked']);
    }

    private function apiHeaderFor(User $user): array
    {
        $plain = ApiToken::generateForUser($user, 'test');
        return [
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
        ];
    }
}
