<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = [
            [
                "name" => "Avery Martinez",
                "email" => "avery@example.com",
                "profile" => [
                    "display_name" => "Avery",
                    "bio" => "Poly-friendly artist exploring Austin's night scene.",
                    "relationship_style" => "polyamorous",
                    "sexual_orientation" => "bisexual",
                    "sti_status" => ["hiv" => "negative", "testedAt" => now()->subMonths(2)->toDateString()],
                    "preferences" => ["looking_for" => ["couples", "friends"], "kinks" => ["rope", "voyeurism"]],
                    "location_description" => "East Austin Loft",
                ],
            ],
            [
                "name" => "Jordan Blake",
                "email" => "jordan@example.com",
                "profile" => [
                    "display_name" => "Coach Jordan",
                    "bio" => "Hosting curated mixers for wellness-minded singles.",
                    "relationship_style" => "monogamish",
                    "sexual_orientation" => "pansexual",
                    "sti_status" => ["hsv" => "positive", "disclosed" => true],
                    "preferences" => ["looking_for" => ["singles"], "venues" => ["wellness studio", "rooftop"]],
                    "location_description" => "Downtown Loft Near 6th",
                ],
            ],
            [
                "name" => "Sky Rivera",
                "email" => "sky@example.com",
                "profile" => [
                    "display_name" => "Sky",
                    "bio" => "Festival DJ seeking after-party collaborators.",
                    "relationship_style" => "open",
                    "sexual_orientation" => "queer",
                    "sti_status" => ["prep" => true],
                    "preferences" => ["looking_for" => ["friends", "event crew"], "music" => ["house", "techno"]],
                    "location_description" => "Riverside Warehouse Studio",
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $user = User::factory()->create([
                "name" => $scenario["name"],
                "email" => $scenario["email"],
            ]);

            $user->profile()->create($scenario["profile"]);
        }
    }
}
