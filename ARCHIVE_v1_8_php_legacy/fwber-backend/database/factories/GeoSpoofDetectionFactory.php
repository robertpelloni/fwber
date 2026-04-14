<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeoSpoofDetection>
 */
class GeoSpoofDetectionFactory extends Factory
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
            'ip_address' => $this->faker->ipv4(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'ip_latitude' => $this->faker->latitude(),
            'ip_longitude' => $this->faker->longitude(),
            'distance_km' => $this->faker->numberBetween(0, 1000),
            'velocity_kmh' => $this->faker->numberBetween(0, 1000),
            'suspicion_score' => $this->faker->numberBetween(0, 100),
            'detection_flags' => ['ip_mismatch', 'high_velocity'],
            'is_confirmed_spoof' => false,
            'detected_at' => now(),
        ];
    }
}
