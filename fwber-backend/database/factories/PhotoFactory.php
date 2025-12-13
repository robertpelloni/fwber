<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => $this->faker->uuid . '.jpg',
            'original_filename' => $this->faker->word . '.jpg',
            'file_path' => 'photos/' . $this->faker->uuid . '.jpg',
            'thumbnail_path' => 'photos/thumbnails/' . $this->faker->uuid . '.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(1000, 5000000),
            'width' => $this->faker->numberBetween(800, 2000),
            'height' => $this->faker->numberBetween(600, 1500),
            'is_primary' => false,
            'is_private' => false,
            'sort_order' => 0,
            'metadata' => [],
        ];
    }
}
