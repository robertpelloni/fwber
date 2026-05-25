<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProximityChatroom>
 */
class ProximityChatroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['conference', 'event', 'venue', 'area', 'temporary']),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'radius_meters' => $this->faker->numberBetween(100, 5000),
            'city' => $this->faker->city(),
            'created_by' => User::factory(),
            'is_active' => true,
            'is_public' => true,
            'max_members' => 1000,
            'current_members' => 0,
            'message_count' => 0,
            'last_activity_at' => now(),
        ];
    }
}
