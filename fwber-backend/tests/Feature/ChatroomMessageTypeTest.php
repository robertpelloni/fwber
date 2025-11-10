<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ChatroomMessageTypeTest extends TestCase
{
    public function test_chatroom_message_store_uses_type_column(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'display_name' => 'Sender',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'location_description' => 'Los Angeles, CA',
        ]);
        $other = User::factory()->create();
        $other->profile()->create([
            'display_name' => 'Receiver',
            'date_of_birth' => '1992-06-15',
            'gender' => 'female',
            'location_description' => 'Los Angeles, CA',
        ]);

        $token = ApiToken::generateForUser($user, 'test');
        $tokenOther = ApiToken::generateForUser($other, 'test');

        // Create a private chatroom between users
        $pairName = 'match_'.min($user->id, $other->id).'_'.max($user->id, $other->id);
        // Enable auto chat creation feature flag
        \Config::set('feature_flags.flags.auto_chat_on_match', true);

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $other->id,
            ])->assertOk();

        Auth::logout();

        $this->withHeader('Authorization', 'Bearer '.$tokenOther)
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $user->id,
            ])->assertOk();

        $chatroomId = \DB::table('chatrooms')->where('name', $pairName)->value('id');
        $this->assertNotNull($chatroomId, 'Chatroom should have been auto-created for mutual match');

        // Send a message specifying type=image
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/chatrooms/{$chatroomId}/messages", [
                'content' => 'photo.png',
                'type' => 'image',
            ])->assertCreated();

        $this->assertDatabaseHas('chatroom_messages', [
            'chatroom_id' => $chatroomId,
            'type' => 'image',
        ]);
    }
}
