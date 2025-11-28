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
                // Align with current API: flat profile fields, not nested under "profile"
                "display_name" => "Updated",
                "bio" => "Updated bio",
                // looking_for is a top-level array per controller contract
                "looking_for" => ["friendship"],
                // preferences is an object; choose a valid subkey from the validator
                "preferences" => [
                    "exercise" => "weekly",
                ],
            ]);

        // Response shape: { success, message, data: UserProfileResource, profile_complete }
        $updateResponse->assertOk()
            ->assertJsonPath("success", true)
            ->assertJsonPath("data.profile.display_name", "Updated")
            ->assertJsonPath("data.profile.bio", "Updated bio");

        // Ensure DB reflects the updated profile
        $this->assertDatabaseHas("users", ["email" => "profile@example.com"]);
        $this->assertDatabaseHas("user_profiles", ["display_name" => "Updated", "bio" => "Updated bio"]);
    }

    public function test_requests_without_token_are_rejected(): void
    {
        $this->getJson("/api/user")->assertUnauthorized();
    }
}
