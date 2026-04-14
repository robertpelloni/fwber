<?php

namespace Database\Factories;

use App\Models\Chatroom;
use App\Models\ChatroomMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatroomMessageFactory extends Factory
{
    protected $model = ChatroomMessage::class;

    public function definition()
    {
        return [
            'chatroom_id' => Chatroom::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => $this->faker->sentence,
            'type' => 'text',
            'metadata' => [],
            'is_edited' => false,
            'is_deleted' => false,
            'is_pinned' => false,
            'is_announcement' => false,
            'reaction_count' => 0,
            'reply_count' => 0,
        ];
    }
}
