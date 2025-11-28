<?php

namespace Database\Factories;

use App\Models\ProximityArtifact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProximityArtifactFactory extends Factory
{
    protected $model = ProximityArtifact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['chat', 'board_post', 'announce']),
            'content' => fake()->sentence(),
            'location_lat' => fake()->latitude(),
            'location_lng' => fake()->longitude(),
            'visibility_radius_m' => 1000,
            'moderation_status' => 'clean',
            'expires_at' => now()->addHours(2),
            'meta' => ['flags_count' => 0],
        ];
    }

    public function chat(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'chat',
            'expires_at' => now()->addMinutes(45),
        ]);
    }

    public function boardPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'board_post',
            'expires_at' => now()->addHours(48),
        ]);
    }

    public function announce(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'announce',
            'expires_at' => now()->addHours(2),
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => 'flagged',
            'meta' => ['flags_count' => 3],
        ]);
    }

    public function removed(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => 'removed',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
