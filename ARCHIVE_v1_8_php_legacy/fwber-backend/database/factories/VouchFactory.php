<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vouch;
use Illuminate\Database\Eloquent\Factories\Factory;

class VouchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vouch::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'to_user_id' => User::factory(),
            'from_user_id' => User::factory(),
            'type' => $this->faker->randomElement(['safe', 'fun', 'hot']),
            'ip_address' => $this->faker->ipv4,
            'relationship_type' => $this->faker->randomElement(['Friend', 'Date', 'Stranger']),
            'comment' => $this->faker->sentence,
            'voucher_name' => $this->faker->name,
        ];
    }
}
