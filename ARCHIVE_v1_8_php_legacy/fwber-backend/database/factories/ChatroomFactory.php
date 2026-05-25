<?php

namespace Database\Factories;

use App\Models\Chatroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatroomFactory extends Factory
{
    protected $model = Chatroom::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['interest', 'city', 'event', 'private']),
            'category' => $this->faker->word,
            'city' => $this->faker->city,
            'neighborhood' => $this->faker->streetName,
            'created_by' => User::factory(),
            'is_public' => $this->faker->boolean(80),
            'is_active' => true,
            'member_count' => 1,
            'message_count' => 0,
            'last_activity_at' => now(),
            'settings' => [],
        ];
    }
}
