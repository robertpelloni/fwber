<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMatch;
use App\Models\ApiToken;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ReadReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private User $a;
    private User $b;
    private string $tokenA;
    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = User::factory()->create();
        $this->b = User::factory()->create();

        // Complete profiles to avoid middleware issues
        UserProfile::factory()->create([
            'user_id' => $this->a->id,
            'display_name' => 'A',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'location_description' => 'A City',
            'location_latitude' => 40.0,
            'location_longitude' => -73.0,
        ]);
        UserProfile::factory()->create([
            'user_id' => $this->b->id,
            'display_name' => 'B',
            'date_of_birth' => now()->subYears(24),
            'gender' => 'female',
            'location_description' => 'B City',
            'location_latitude' => 40.1,
            'location_longitude' => -73.1,
        ]);

        $this->tokenA = ApiToken::generateForUser($this->a, 'test');
        $this->tokenB = ApiToken::generateForUser($this->b, 'test');

        // Create active match between A and B
        UserMatch::create([
            'user1_id' => $this->a->id,
            'user2_id' => $this->b->id,
            'is_active' => true,
            'last_message_at' => null,
        ]);
    }

    #[Test]
    public function receiver_can_mark_message_as_read_idempotently(): void
    {
        // A sends message to B
        $send = $this->withHeader('Authorization', 'Bearer ' . $this->tokenA)
            ->postJson('/api/messages', [
                'receiver_id' => $this->b->id,
                'content' => 'hello',
            ]);
        $send->assertStatus(201);
        $messageId = $send->json('message.id');

        // Initially unread
        $message = Message::find($messageId);
        $this->assertFalse($message->is_read);
        $this->assertNull($message->read_at);

        // B marks as read
        $r1 = $this->withHeader('Authorization', 'Bearer ' . $this->tokenB)
            ->postJson("/api/messages/{$messageId}/read");
        $r1->assertOk();
        $r1->assertJsonPath('message.is_read', true);
        $firstReadAt = $r1->json('message.read_at');
        $this->assertNotNull($firstReadAt);

        // Second call should be idempotent (timestamp unchanged)
        $r2 = $this->withHeader('Authorization', 'Bearer ' . $this->tokenB)
            ->postJson("/api/messages/{$messageId}/read");
        $r2->assertOk();
        $r2->assertJsonPath('message.is_read', true);
        $this->assertSame($firstReadAt, $r2->json('message.read_at'));
    }

    #[Test]
    public function sender_cannot_mark_message_as_read(): void
    {
        // A sends to B
        $send = $this->withHeader('Authorization', 'Bearer ' . $this->tokenA)
            ->postJson('/api/messages', [
                'receiver_id' => $this->b->id,
                'content' => 'blocked try',
            ]);
        $send->assertStatus(201);
        $messageId = $send->json('message.id');

        // A attempts to mark as read -> 403
        $resp = $this->withHeader('Authorization', 'Bearer ' . $this->tokenA)
            ->postJson("/api/messages/{$messageId}/read");
        $resp->assertStatus(403);
    }

    #[Test]
    public function marking_nonexistent_message_returns_404(): void
    {
        $resp = $this->withHeader('Authorization', 'Bearer ' . $this->tokenB)
            ->postJson('/api/messages/999999/read');
        $resp->assertStatus(404);
    }
}
