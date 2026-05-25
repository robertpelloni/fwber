<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModerationAction>
 */
class ModerationActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'moderator_id' => User::factory(),
            'target_user_id' => User::factory(),
            'action_type' => $this->faker->randomElement(['ban', 'suspend', 'warn']),
            'reason' => $this->faker->sentence,
        ];
    }
}
