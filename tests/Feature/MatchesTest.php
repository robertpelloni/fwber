<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Tests\TestCase;

class MatchesTest extends TestCase
{
    public function test_authenticated_user_receives_match_list(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            "display_name" => "Primary",
            "date_of_birth" => "1990-01-01",
            "gender" => "male",
            "location_description" => "Austin, TX",
        ]);

        $others = User::factory()->count(2)->create();

        foreach ($others as $index => $candidate) {
            $candidate->profile()->create([
                "display_name" => "Candidate " . ($index + 1),
                "date_of_birth" => "1992-06-15",
                "gender" => "female",
                "bio" => "Bio " . ($index + 1),
                "location_description" => "Austin, TX",
            ]);
        }

        $token = ApiToken::generateForUser($user, "test");

        $response = $this->withHeader("Authorization", "Bearer " . $token)->getJson("/api/matches");

        $response->assertOk()->assertJsonStructure([
            "matches" => [
                [
                    "id",
                    "name",
                    "email",
                    "avatarUrl",
                    "bio",
                    "locationDescription",
                    "compatibilityScore",
                ],
            ],
        ]);
    }
}
