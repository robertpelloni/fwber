<?php

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\MerchantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Promotion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => MerchantProfile::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'discount_value' => (string) $this->faker->numberBetween(5, 50),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),
            'radius' => 100,
            'token_cost' => 0,
            'starts_at' => now(),   // Matches migration column 'starts_at'
            'expires_at' => now()->addDays(7), // Matches migration column 'expires_at'
            'is_active' => true,
            // 'views', 'clicks', 'redemptions' removed as they aren't in the migration
        ];
    }
}
