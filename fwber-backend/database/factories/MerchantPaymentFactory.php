<?php

namespace Database\Factories;

use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MerchantPayment>
 */
class MerchantPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MerchantPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'merchant_id' => User::factory(), // Matches foreignId('merchant_id')->constrained('users')
            'payer_id' => User::factory(),    // Matches foreignId('payer_id')->constrained('users')
            'amount' => $this->faker->numberBetween(10, 500),
            'status' => 'paid',               // Changed from 'completed' to 'paid' to match migration
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
