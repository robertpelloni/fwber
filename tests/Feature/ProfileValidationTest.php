<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProfileValidationTest extends TestCase
{
    public function test_invalid_looking_for_value_returns_422(): void
    {
        // Register a user to obtain a token
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Val User',
            'email' => 'val@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $token = $registerResponse->json('token');

        // Send an invalid looking_for enum
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user', [
                'display_name' => 'X',
                'bio' => 'Y',
                'looking_for' => ['invalid-value'],
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['looking_for.0'],
            ]);
    }

    public function test_underage_date_of_birth_is_rejected(): void
    {
        // Register a user to obtain a token
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Underage User',
            'email' => 'underage@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $token = $registerResponse->json('token');

        // Provide a DOB that makes the user younger than 18
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user', [
                'display_name' => 'Young',
                'date_of_birth' => now()->subYears(16)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['date_of_birth'],
            ]);
    }
}
