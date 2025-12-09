<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BulletinBoard>
 */
class BulletinBoardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'geohash' => $this->faker->lexify('??????'),
            'center_lat' => $this->faker->latitude(),
            'center_lng' => $this->faker->longitude(),
            'radius_meters' => $this->faker->numberBetween(500, 5000),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'message_count' => 0,
            'active_users' => 0,
            'last_activity_at' => now(),
        ];
    }
}
