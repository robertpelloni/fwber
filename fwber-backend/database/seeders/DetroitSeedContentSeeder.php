<?php

namespace Database\Seeders;

use App\Models\ProximityArtifact;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DetroitSeedContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Neighborhood Centers
        $locations = [
            'Midtown' => ['lat' => 42.3484, 'lng' => -83.0603],
            'Corktown' => ['lat' => 42.3317, 'lng' => -83.0664],
            'Downtown' => ['lat' => 42.3314, 'lng' => -83.0458],
            'Eastern Market' => ['lat' => 42.3489, 'lng' => -83.0400],
            'West Village' => ['lat' => 42.3567, 'lng' => -82.9961],
        ];

        $posts = [
            ['Midtown', "Grabbing a pour-over at Anthology. The vibe is perfect for a first date. Anyone nearby?"],
            ['Midtown', "Checking out the new exhibit at the DIA. Truly stunning. #DetroitArts"],
            ['Corktown', "Slows BBQ is packed tonight! Who wants to grab a drink while we wait for a table?"],
            ['Corktown', "Is it just me or is Michigan Ave getting more beautiful every day?"],
            ['Downtown', "The Belt is glowing tonight. Perfect spot for a neon-noir photo op."],
            ['Downtown', "Just finished a walk along the Riverfront. Detroit, I love you."],
            ['Eastern Market', "Found the best vintage leather jacket at the Saturday market! ✨"],
            ['Eastern Market', "Flowers, food, and community. Nothing beats a morning here."],
            ['West Village', "Sister Pie cookies are dangerous. I've had three already. Help."],
            ['West Village', "Does anyone know if the tennis courts at Belle Isle are open today?"],
        ];

        foreach ($posts as $index => $postData) {
            $neighborhood = $postData[0];
            $content = $postData[1];
            $coords = $locations[$neighborhood];

            // Create or find a seed user for each post
            $user = User::updateOrCreate(
                ['email' => "seed" . ($index + 1) . "@fwber.test"],
                [
                    'name' => "SeedUser_" . ($index + 1),
                    'password' => Hash::make('password'),
                    'token_balance' => 100,
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => "Detroit local. Love " . $neighborhood . ".",
                    'latitude' => $coords['lat'] + (rand(-100, 100) / 100000), // Tiny variance
                    'longitude' => $coords['lng'] + (rand(-100, 100) / 100000),
                    'location_name' => $neighborhood . ", Detroit",
                    'is_verified' => true,
                ]
            );

            ProximityArtifact::create([
                'user_id' => $user->id,
                'type' => 'board_post',
                'content' => $content,
                'location_lat' => $coords['lat'],
                'location_lng' => $coords['lng'],
                'visibility_radius_m' => 5000, // 5km
                'moderation_status' => 'clean',
                'expires_at' => now()->addDays(30),
            ]);
        }
    }
}