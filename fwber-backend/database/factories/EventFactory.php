<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'type' => $this->faker->randomElement(['standard', 'speed_dating', 'party', 'meetup', 'workshop']),
            'location_name' => $this->faker->city,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'starts_at' => now()->addDays(rand(1, 30)),
            'ends_at' => now()->addDays(rand(1, 30))->addHours(2),
            'max_attendees' => rand(10, 100),
            'price' => rand(0, 50),
            'created_by_user_id' => User::factory(),
            'status' => 'upcoming',
        ];
    }
}
