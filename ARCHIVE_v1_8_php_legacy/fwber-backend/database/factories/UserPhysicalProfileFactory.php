<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPhysicalProfile>
 */
class UserPhysicalProfileFactory extends Factory
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
            'height_cm' => $this->faker->numberBetween(150, 200),
            'body_type' => $this->faker->randomElement(['athletic', 'average', 'slim']),
            'hair_color' => $this->faker->colorName,
            'eye_color' => $this->faker->colorName,
        ];
    }
}
