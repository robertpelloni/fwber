<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'business_type' => fake()->randomElement(['bar', 'club', 'restaurant', 'cafe']),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'zip_code' => fake()->postcode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'operating_hours' => [
                'monday' => ['open' => '09:00', 'close' => '22:00'],
                'tuesday' => ['open' => '09:00', 'close' => '22:00'],
                'wednesday' => ['open' => '09:00', 'close' => '22:00'],
                'thursday' => ['open' => '09:00', 'close' => '23:00'],
                'friday' => ['open' => '09:00', 'close' => '02:00'],
                'saturday' => ['open' => '10:00', 'close' => '02:00'],
                'sunday' => ['open' => '10:00', 'close' => '22:00'],
            ],
            'max_capacity' => fake()->numberBetween(50, 500),
            'commission_rate' => fake()->randomFloat(2, 0, 15),
            'verification_status' => 'verified',
            'subscription_tier' => 'starter',
            'is_active' => true,
            'features' => ['wifi', 'parking', 'outdoor_seating'],
        ];
    }
}
