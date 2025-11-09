<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MutualMatchChatroomTest extends TestCase
{
    public function test_mutual_like_creates_chatroom_and_system_message_when_flag_enabled(): void
    {
        // Enable flag
        Config::set('feature_flags.flags.auto_chat_on_match', true);

        $a = User::factory()->create();
        $a->profile()->create(['display_name' => 'A']);
        $b = User::factory()->create();
        $b->profile()->create(['display_name' => 'B']);

        $tokenA = ApiToken::generateForUser($a, 'test');
        $tokenB = ApiToken::generateForUser($b, 'test');

        // A likes B
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/match/action', [
                'action' => 'like',
                'target_user_id' => $b->id,
            ])->assertOk();

        // B likes A (mutual)
        $resp = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->postJson('/api/match/action', [
                'action' => 'like',
                'target_user_id' => $a->id,
            ])->assertOk()->json();

        $this->assertTrue($resp['is_match'] ?? false);

        // Verify chatroom exists
        $pairName = 'match_'.min($a->id, $b->id).'_'.max($a->id, $b->id);
        $this->assertDatabaseHas('chatrooms', [
            'name' => $pairName,
            'type' => 'private',
        ]);

        $chatroomId = \DB::table('chatrooms')->where('name', $pairName)->value('id');

        // Members attached
        $this->assertDatabaseHas('chatroom_members', ['chatroom_id' => $chatroomId, 'user_id' => $a->id]);
        $this->assertDatabaseHas('chatroom_members', ['chatroom_id' => $chatroomId, 'user_id' => $b->id]);

        // System message present
        $this->assertDatabaseHas('chatroom_messages', [
            'chatroom_id' => $chatroomId,
            'type' => 'system',
        ]);
    }
}
