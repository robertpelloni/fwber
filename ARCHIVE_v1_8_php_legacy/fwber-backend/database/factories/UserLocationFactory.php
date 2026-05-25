<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserLocation>
 */
class UserLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'accuracy' => $this->faker->randomFloat(2, 0, 100),
            'is_active' => true,
            'privacy_level' => 'public',
        ];
    }
}
