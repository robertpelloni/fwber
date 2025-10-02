<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_authenticated_user_can_update_profile(): void
    {
        $registerResponse = $this->postJson("/api/auth/register", [
            "name" => "Example User",
            "email" => "profile@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ])->assertCreated();

        $token = $registerResponse->json("token");

        $updateResponse = $this->withHeader("Authorization", "Bearer " . $token)
            ->putJson("/api/user", [
                "name" => "Updated User",
                "profile" => [
                    "display_name" => "Updated",
                    "bio" => "Updated bio",
                    "preferences" => ["looking_for" => "friends"],
                ],
            ]);

        $updateResponse->assertOk()->assertJsonPath("user.name", "Updated User")
            ->assertJsonPath("user.profile.display_name", "Updated");

        $this->assertDatabaseHas("users", ["email" => "profile@example.com", "name" => "Updated User"]);
        $this->assertDatabaseHas("user_profiles", ["display_name" => "Updated"]);
    }

    public function test_requests_without_token_are_rejected(): void
    {
        $this->getJson("/api/user")->assertUnauthorized();
    }
}
