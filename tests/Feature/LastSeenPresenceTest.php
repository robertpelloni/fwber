<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ApiToken;
use Tests\Traits\RefreshDatabaseSilently;
use PHPUnit\Framework\Attributes\Test;

class LastSeenPresenceTest extends TestCase
{
    use RefreshDatabaseSilently;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Presence User',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'location_description' => 'Some City',
            'location_latitude' => 40.0,
            'location_longitude' => -73.0,
        ]);
        $this->token = ApiToken::generateForUser($this->user, 'test');
    }

    #[Test]
    public function last_seen_updates_on_authenticated_request_with_debounce(): void
    {
        $this->assertNull($this->user->fresh()->last_seen_at);

        // First request (matches feed triggers presence update)
        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/matches');
        $resp1->assertOk();
        $first = $this->user->fresh()->last_seen_at;
        $this->assertNotNull($first);

        // Immediate second request should not change timestamp (debounce 60s)
        $resp2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/matches');
        $resp2->assertOk();
        $second = $this->user->fresh()->last_seen_at;
        $this->assertEquals($first, $second);
    }
}
