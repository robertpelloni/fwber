<?php

namespace Database\Factories;

use App\Models\BulletinBoard;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulletinBoardFactory extends Factory
{
    protected $model = BulletinBoard::class;

    public function definition(): array
    {
        return [
            // Provide a geohash to satisfy NOT NULL constraint in migration
            'geohash' => substr(md5($this->faker->unique()->word()), 0, 10),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(12),
            'center_lat' => $this->faker->latitude(),
            'center_lng' => $this->faker->longitude(),
        ];
    }
}
