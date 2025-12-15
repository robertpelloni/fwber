<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViralContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViralContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_content_without_incrementing_views()
    {
        $user = User::factory()->create();
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => ['text' => 'test'],
        ]);

        $this->actingAs($user)
            ->getJson("/api/viral-content/{$content->id}")
            ->assertOk()
            ->assertJson(['views' => 0, 'is_owner' => true]);

        $this->assertEquals(0, $content->fresh()->views);
    }

    public function test_guest_view_increments_views()
    {
        $user = User::factory()->create();
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => ['text' => 'test'],
        ]);

        $this->getJson("/api/viral-content/{$content->id}")
            ->assertOk()
            ->assertJson(['views' => 1, 'is_owner' => false]);

        $this->assertEquals(1, $content->fresh()->views);
    }

    public function test_other_user_view_increments_views()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => ['text' => 'test'],
        ]);

        $this->actingAs($otherUser)
            ->getJson("/api/viral-content/{$content->id}")
            ->assertOk()
            ->assertJson(['views' => 1, 'is_owner' => false]);

        $this->assertEquals(1, $content->fresh()->views);
    }

    public function test_reward_is_granted_at_5_views()
    {
        $user = User::factory()->create(['tier' => 'free']);
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => ['text' => 'test'],
            'views' => 4,
        ]);

        // 5th view
        $this->getJson("/api/viral-content/{$content->id}")->assertOk();

        $content->refresh();
        $user->refresh();

        $this->assertEquals(5, $content->views);
        $this->assertTrue((bool)$content->reward_claimed);
        $this->assertEquals('gold', $user->tier);
        $this->assertTrue($user->tier_expires_at->isFuture());
    }
}
