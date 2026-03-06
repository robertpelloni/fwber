<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProximityArtifactComment>
 */
class ProximityArtifactCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proximity_artifact_id' => \App\Models\ProximityArtifact::factory(),
            'user_id' => \App\Models\User::factory(),
            'content' => $this->faker->sentence(),
        ];
    }
}
