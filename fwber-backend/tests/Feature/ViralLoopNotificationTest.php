<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViralContent;
use App\Services\TokenDistributionService;
use App\Notifications\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ViralLoopNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_award_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create(['referral_code' => 'TESTTOKEN']);
        $service = new TokenDistributionService();

        $service->awardTokens($user, 100, 'test_bonus', 'Test Bonus');

        Notification::assertSentTo(
            [$user],
            PushMessage::class,
            function ($notification, $channels) {
                return $notification->title === '💰 Tokens Received!';
            }
        );
    }

    public function test_vouch_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create(['referral_code' => 'TESTVOUCH']);

        $response = $this->postJson('/api/public/vouch', [
            'referral_code' => $user->referral_code,
            'type' => 'hot',
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            [$user],
            PushMessage::class,
            function ($notification, $channels) {
                return str_contains($notification->title, 'New Vouch!');
            }
        );
    }

    public function test_viral_content_reward_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create(['referral_code' => 'TESTVIRAL']);
        $content = ViralContent::create([
            'user_id' => $user->id,
            'type' => 'roast',
            'content' => 'Test content',
            'views' => 4, // 1 view away from reward (5)
            'reward_claimed' => false,
        ]);

        // Hit the show endpoint to increment view
        $response = $this->getJson("/api/viral-content/{$content->id}");
        $response->assertStatus(200);

        Notification::assertSentTo(
            [$user],
            PushMessage::class,
            function ($notification, $channels) {
                return $notification->title === 'Viral Gold Unlocked! 🏆';
            }
        );
    }
}
