<?php

namespace Tests\Feature;

use App\Mail\NewMatchNotification;
use App\Mail\UnreadMessagesNotification;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMatch;
use App\Models\Message;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private EmailNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
        
        // Enable email notifications flag
        Config::set('feature_flags.flags.email_notifications', true);
        
        $this->service = app(EmailNotificationService::class);
    }

    #[Test]
    public function new_match_sends_email_to_both_users(): void
    {
        $userA = User::factory()->create(['email' => 'usera@example.com']);
        $profileA = UserProfile::factory()->create([
            'user_id' => $userA->id,
            'display_name' => 'User A',
            'bio' => 'Hello from A',
            'date_of_birth' => now()->subYears(25),
            'gender' => 'male',
            'location_latitude' => 40.7128,
            'location_longitude' => -74.0060,
        ]);

        $userB = User::factory()->create(['email' => 'userb@example.com']);
        $profileB = UserProfile::factory()->create([
            'user_id' => $userB->id,
            'display_name' => 'User B',
            'bio' => 'Hello from B',
            'date_of_birth' => now()->subYears(26),
            'gender' => 'female',
            'location_latitude' => 40.7200,
            'location_longitude' => -74.0100,
        ]);

        // Simulate new match via API action
        $tokenA = ApiToken::generateForUser($userA, 'test');
        
        $response = $this->withHeader('Authorization', "Bearer $tokenA")
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $userB->id,
            ]);

        $response->assertOk();
        $this->assertFalse($response->json('is_match')); // Not mutual yet

        // UserB likes back
        $tokenB = ApiToken::generateForUser($userB, 'test');
        
        $response = $this->withHeader('Authorization', "Bearer $tokenB")
            ->postJson('/api/matches/action', [
                'action' => 'like',
                'target_user_id' => $userA->id,
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_match')); // Mutual!

        // Verify emails sent to both
        Mail::assertSent(NewMatchNotification::class, 2);
        
        Mail::assertSent(NewMatchNotification::class, function ($mail) use ($userA) {
            return $mail->hasTo($userA->email);
        });
        
        Mail::assertSent(NewMatchNotification::class, function ($mail) use ($userB) {
            return $mail->hasTo($userB->email);
        });
    }

    #[Test]
    public function unread_messages_notification_debounces_correctly(): void
    {
        $receiver = User::factory()->create(['email' => 'receiver@example.com']);
        $sender = User::factory()->create(['name' => 'Sender']);

        UserProfile::factory()->create(['user_id' => $receiver->id]);
        UserProfile::factory()->create(['user_id' => $sender->id]);

        UserMatch::create([
            'user1_id' => min($receiver->id, $sender->id),
            'user2_id' => max($receiver->id, $sender->id),
            'is_active' => true,
        ]);

        // Create unread message
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => 'Hey there, how are you doing today?',
            'sent_at' => now(),
            'read_at' => null,
        ]);

        // First notification should send
        $this->service->sendUnreadMessagesNotification($receiver);
        
        Mail::assertSent(UnreadMessagesNotification::class, 1);
        Mail::assertSent(UnreadMessagesNotification::class, function ($mail) use ($receiver) {
            return $mail->hasTo($receiver->email) 
                && $mail->unreadCount === 1;
        });

        // Second call within debounce period should NOT send
        Mail::fake(); // Reset
        $this->service->sendUnreadMessagesNotification($receiver);
        
        Mail::assertNothingSent();
    }

    #[Test]
    public function unread_notification_groups_messages_by_sender(): void
    {
        $receiver = User::factory()->create(['email' => 'receiver@example.com']);
        $sender1 = User::factory()->create(['name' => 'Alice']);
        $sender2 = User::factory()->create(['name' => 'Bob']);

        UserProfile::factory()->create(['user_id' => $receiver->id]);
        UserProfile::factory()->create(['user_id' => $sender1->id]);
        UserProfile::factory()->create(['user_id' => $sender2->id]);

        // Create matches
        UserMatch::create([
            'user1_id' => min($receiver->id, $sender1->id),
            'user2_id' => max($receiver->id, $sender1->id),
            'is_active' => true,
        ]);
        UserMatch::create([
            'user1_id' => min($receiver->id, $sender2->id),
            'user2_id' => max($receiver->id, $sender2->id),
            'is_active' => true,
        ]);

        // Multiple messages from sender1
        Message::create([
            'sender_id' => $sender1->id,
            'receiver_id' => $receiver->id,
            'content' => 'Message 1 from Alice',
            'sent_at' => now()->subMinutes(10),
        ]);
        Message::create([
            'sender_id' => $sender1->id,
            'receiver_id' => $receiver->id,
            'content' => 'Message 2 from Alice',
            'sent_at' => now()->subMinutes(5),
        ]);

        // One message from sender2
        Message::create([
            'sender_id' => $sender2->id,
            'receiver_id' => $receiver->id,
            'content' => 'Message from Bob',
            'sent_at' => now()->subMinutes(2),
        ]);

        $this->service->sendUnreadMessagesNotification($receiver);

        Mail::assertSent(UnreadMessagesNotification::class, function ($mail) use ($receiver) {
            return $mail->hasTo($receiver->email)
                && $mail->unreadCount === 3
                && count($mail->senders) === 2; // 2 unique senders
        });
    }

    #[Test]
    public function email_notifications_disabled_by_flag(): void
    {
        Config::set('feature_flags.flags.email_notifications', false);
        
        // Force new instance of FeatureFlagService
        app()->forgetInstance(\App\Services\FeatureFlagService::class);
        $service = new EmailNotificationService(app(\App\Services\FeatureFlagService::class));
        
        $userA = User::factory()->create(['email' => 'a@example.com']);
        $userB = User::factory()->create(['email' => 'b@example.com']);
        
        UserProfile::factory()->create(['user_id' => $userA->id]);
        UserProfile::factory()->create(['user_id' => $userB->id]);

        $service->sendNewMatchNotification($userA, $userB);
        
        Mail::assertNothingSent();
    }

    #[Test]
    public function batch_unread_notifications_sends_to_all_recipients(): void
    {
        $receiver1 = User::factory()->create(['email' => 'r1@example.com']);
        $receiver2 = User::factory()->create(['email' => 'r2@example.com']);
        $sender = User::factory()->create();

        foreach ([$receiver1, $receiver2, $sender] as $user) {
            UserProfile::factory()->create(['user_id' => $user->id]);
        }

        UserMatch::create([
            'user1_id' => min($receiver1->id, $sender->id),
            'user2_id' => max($receiver1->id, $sender->id),
            'is_active' => true,
        ]);
        UserMatch::create([
            'user1_id' => min($receiver2->id, $sender->id),
            'user2_id' => max($receiver2->id, $sender->id),
            'is_active' => true,
        ]);

        // Unread messages to both receivers
        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
            'content' => 'Message for receiver 1',
            'sent_at' => now(),
        ]);
        Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver2->id,
            'content' => 'Message for receiver 2',
            'sent_at' => now(),
        ]);

        $sent = $this->service->sendBatchUnreadNotifications();

        $this->assertEquals(2, $sent);
        Mail::assertSent(UnreadMessagesNotification::class, 2);
    }
}
