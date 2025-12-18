<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\User;
use App\Models\UserGift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\GiftSeeder::class);
    }

    public function test_can_list_gifts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/gifts');

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data'); // 6 gifts in seeder
    }

    public function test_can_send_gift_with_sufficient_tokens()
    {
        $sender = User::factory()->create(['token_balance' => 100, 'last_daily_bonus_at' => now()]);
        $receiver = User::factory()->create();
        $gift = Gift::where('name', 'Rose')->first(); // Cost 10

        $response = $this->actingAs($sender)->postJson('/api/gifts/send', [
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
            'message' => 'For you!',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $sender->id,
            'token_balance' => 90,
        ]);

        $this->assertDatabaseHas('user_gifts', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
            'message' => 'For you!',
        ]);
    }

    public function test_cannot_send_gift_with_insufficient_tokens()
    {
        $sender = User::factory()->create(['token_balance' => 5, 'last_daily_bonus_at' => now()]);
        $receiver = User::factory()->create();
        $gift = Gift::where('name', 'Rose')->first(); // Cost 10

        $response = $this->actingAs($sender)->postJson('/api/gifts/send', [
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
        ]);

        $response->assertStatus(400);
        
        $this->assertDatabaseHas('users', [
            'id' => $sender->id,
            'token_balance' => 5,
        ]);
    }

    public function test_cannot_send_gift_to_self()
    {
        $sender = User::factory()->create(['token_balance' => 100]);
        $gift = Gift::where('name', 'Rose')->first();

        $response = $this->actingAs($sender)->postJson('/api/gifts/send', [
            'receiver_id' => $sender->id,
            'gift_id' => $gift->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_can_list_received_gifts()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $gift = Gift::where('name', 'Rose')->first();

        UserGift::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'gift_id' => $gift->id,
            'cost_at_time' => $gift->cost,
            'message' => 'Hello',
        ]);

        $response = $this->actingAs($receiver)->getJson('/api/gifts/received');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Hello');
    }
}
