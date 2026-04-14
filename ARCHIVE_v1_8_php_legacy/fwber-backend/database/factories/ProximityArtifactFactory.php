<?php

namespace Database\Factories;

use App\Models\ProximityArtifact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProximityArtifactFactory extends Factory
{
    protected $model = ProximityArtifact::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'type' => 'board_post',
            'content' => $this->faker->sentence(),
            'location_lat' => $this->faker->latitude,
            'location_lng' => $this->faker->longitude,
            'visibility_radius_m' => 1000,
            'moderation_status' => 'clean',
            'expires_at' => now()->addHours(24),
            'meta' => null,
            'is_flagged' => false,
            'flag_count' => 0,
        ];
    }
}
