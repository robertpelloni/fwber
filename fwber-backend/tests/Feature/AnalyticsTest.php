<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\ClickstreamEvent;
use App\Models\User;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_clickstream_events_unauthenticated()
    {
        $payload = [
            'session_id' => 'test_session_123',
            'events' => [
                [
                    'event_name' => 'page_view',
                    'payload' => ['page' => 'home'],
                    'url' => 'https://fwber.me/'
                ],
                [
                    'event_name' => 'button_click',
                    'payload' => ['button' => 'signup'],
                ]
            ]
        ];

        $response = $this->postJson('/api/analytics/events', $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('clickstream_events', 2);
        
        $this->assertDatabaseHas('clickstream_events', [
            'session_id' => 'test_session_123',
            'event_name' => 'page_view',
            'url' => 'https://fwber.me/',
        ]);

        $this->assertDatabaseHas('clickstream_events', [
            'session_id' => 'test_session_123',
            'event_name' => 'button_click',
        ]);
        
        // Assert payload is properly saved
        $event = ClickstreamEvent::where('event_name', 'page_view')->first();
        $this->assertEquals(['page' => 'home'], $event->payload);
        $this->assertNull($event->user_id);
    }

    public function test_can_record_clickstream_events_authenticated()
    {
        $user = User::factory()->create();

        $payload = [
            'session_id' => 'test_session_456',
            'events' => [
                [
                    'event_name' => 'item_viewed',
                    'payload' => ['item_id' => 99],
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/analytics/events', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('clickstream_events', [
            'session_id' => 'test_session_456',
            'event_name' => 'item_viewed',
            'user_id' => $user->id,
        ]);
    }
    
    public function test_validation_fails_without_session_id()
    {
        $payload = [
            'events' => [
                [
                    'event_name' => 'page_view',
                ]
            ]
        ];

        $response = $this->postJson('/api/analytics/events', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['session_id']);
    }
}
