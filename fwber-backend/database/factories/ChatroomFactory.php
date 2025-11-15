<?php

namespace Database\Factories;

use App\Models\Chatroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatroomFactory extends Factory
{
    protected $model = Chatroom::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['interest', 'city', 'event', 'private']),
            'category' => $this->faker->randomElement(['music', 'fitness', 'food', 'tech', 'gaming']),
            'city' => $this->faker->city(),
            'neighborhood' => $this->faker->streetName(),
            'created_by' => User::factory(),
            'is_public' => true,
            'is_active' => true,
            'member_count' => 0,
            'message_count' => 0,
            'last_activity_at' => now(),
            'settings' => null,
        ];
    }

    /**
     * Indicate that the chatroom is private
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'private',
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the chatroom is interest-based
     */
    public function interest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'interest',
        ]);
    }

    /**
     * Indicate that the chatroom is city-based
     */
    public function city(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'city',
        ]);
    }

    /**
     * Indicate that the chatroom is event-based
     */
    public function event(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'event',
        ]);
    }

    /**
     * Indicate that the chatroom is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
