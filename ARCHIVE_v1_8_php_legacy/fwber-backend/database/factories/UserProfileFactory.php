<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'bio' => $this->faker->sentence,
            'birthdate' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'location_name' => $this->faker->city,
        ];
    }
}
