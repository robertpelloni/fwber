<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => $this->faker->userName(),
            'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'non-binary', 'other']),
            'pronouns' => $this->faker->randomElement(['he/him', 'she/her', 'they/them', 'other']),
            'sexual_orientation' => $this->faker->randomElement(['straight', 'gay', 'lesbian', 'bisexual', 'pansexual', 'asexual', 'other']),
            'relationship_style' => $this->faker->randomElement(['monogamous', 'non-monogamous', 'polyamorous', 'open', 'other']),
            'bio' => $this->faker->paragraph(),
            'location_latitude' => $this->faker->latitude(),
            'location_longitude' => $this->faker->longitude(),
            'location_description' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'sti_status' => null,
            'preferences' => null,
            'avatar_url' => null,
            'looking_for' => ['dating', 'friendship'],
        ];
    }

    /**
     * Indicate that the profile is incomplete (missing required fields)
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_name' => null,
            'date_of_birth' => null,
        ]);
    }

    /**
     * Indicate that the profile has no location
     */
    public function noLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_latitude' => null,
            'location_longitude' => null,
            'location_description' => null,
        ]);
    }
}
