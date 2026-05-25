<?php

namespace Database\Seeders;

use App\Models\Gift;
use Illuminate\Database\Seeder;

class GiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gifts = [
            [
                'name' => 'Rose',
                'description' => 'A classic symbol of romance.',
                'cost' => 10,
                'icon_url' => '/images/gifts/rose.svg',
                'category' => 'romantic',
            ],
            [
                'name' => 'Coffee',
                'description' => 'Let\'s grab a coffee sometime.',
                'cost' => 5,
                'icon_url' => '/images/gifts/coffee.svg',
                'category' => 'casual',
            ],
            [
                'name' => 'Cocktail',
                'description' => 'Cheers!',
                'cost' => 15,
                'icon_url' => '/images/gifts/cocktail.svg',
                'category' => 'party',
            ],
            [
                'name' => 'Teddy Bear',
                'description' => 'Sending you a hug.',
                'cost' => 25,
                'icon_url' => '/images/gifts/teddy.svg',
                'category' => 'cute',
            ],
            [
                'name' => 'Diamond',
                'description' => 'You are precious.',
                'cost' => 100,
                'icon_url' => '/images/gifts/diamond.svg',
                'category' => 'luxury',
            ],
            [
                'name' => 'Rocket',
                'description' => 'To the moon!',
                'cost' => 50,
                'icon_url' => '/images/gifts/rocket.svg',
                'category' => 'fun',
            ],
        ];

        foreach ($gifts as $gift) {
            Gift::firstOrCreate(['name' => $gift['name']], $gift);
        }
    }
}
