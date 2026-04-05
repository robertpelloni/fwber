<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GiftRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_gifts_are_listed(): void
    {
        Gift::query()->create([
            'name' => 'Rose',
            'description' => 'A classic symbol of romance.',
            'cost' => 10,
            'icon_url' => '/images/gifts/rose.svg',
            'category' => 'romantic',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/gifts')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Rose']);
    }

    public function test_user_can_send_gift_and_balances_are_transferred(): void
    {
        Notification::fake();

        $sender = User::factory()->create(['token_balance' => 100]);
        $receiver = User::factory()->create(['token_balance' => 10]);
        $gift = Gift::query()->create([
            'name' => 'Rose',
            'description' => 'A classic symbol of romance.',
            'cost' => 10,
            'icon_url' => '/images/gifts/rose.svg',
            'category' => 'romantic',
            'is_active' => true,
        ]);

        $this->actingAs($sender)
            ->postJson('/api/gifts/send', [
                'receiver_id' => $receiver->id,
                'gift_id' => $gift->id,
                'message' => 'A rose for you!',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Gift sent successfully!');

        $sender->refresh();
        $receiver->refresh();

        $this->assertSame('90.00', number_format((float) $sender->token_balance, 2, '.', ''));
        $this->assertSame('20.00', number_format((float) $receiver->token_balance, 2, '.', ''));

        $this->assertDatabaseHas('user_gifts', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $sender->id,
            'type' => 'gift_sent',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $receiver->id,
            'type' => 'gift_received',
        ]);
    }

    public function test_received_gifts_are_returned_for_wallet_gifts_tab(): void
    {
        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create();
        $gift = Gift::query()->create([
            'name' => 'Diamond',
            'description' => 'You are precious.',
            'cost' => 100,
            'icon_url' => '/images/gifts/diamond.svg',
            'category' => 'luxury',
            'is_active' => true,
        ]);

        \App\Models\UserGift::query()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
            'message' => 'Shine bright.',
            'cost_at_time' => 100,
        ]);

        $this->actingAs($receiver)
            ->getJson('/api/gifts/received')
            ->assertOk()
            ->assertJsonPath('data.0.gift.name', 'Diamond')
            ->assertJsonPath('data.0.sender.name', 'Sender');
    }
}
