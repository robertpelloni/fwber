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
            'storage_path' => 'photos/'.$this->faker->uuid.'.jpg',
            'url' => $this->faker->imageUrl(),
            'is_primary' => false,
            'is_private' => false,
            'blur_level' => 0,
            'is_encrypted' => false,
            'order' => 0,
        ];
    }
}
