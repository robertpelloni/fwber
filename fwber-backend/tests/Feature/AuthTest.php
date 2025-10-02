<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Example User",
            "email" => "user@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
            "profile" => [
                "display_name" => "Example",
                "bio" => "Testing profile creation.",
                "sti_status" => ["hiv" => "negative"],
            ],
        ]);

        $response->assertCreated()->assertJsonStructure([
            "token",
            "user" => ["id", "name", "email", "profile"],
        ]);

        $this->assertDatabaseHas("users", ["email" => "user@example.com"]);
        $this->assertDatabaseHas("user_profiles", ["display_name" => "Example"]);
        $this->assertDatabaseHas("api_tokens", ["user_id" => 1]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            "password" => Hash::make("password123"),
            "email" => "login@example.com",
        ]);
        $user->profile()->create();

        $response = $this->postJson("/api/auth/login", [
            "email" => "login@example.com",
            "password" => "password123",
        ]);

        $response->assertOk()->assertJsonStructure([
            "token",
            "user" => ["id", "name", "email", "profile"],
        ]);

        $this->assertDatabaseHas("api_tokens", ["user_id" => $user->id]);
    }
}
