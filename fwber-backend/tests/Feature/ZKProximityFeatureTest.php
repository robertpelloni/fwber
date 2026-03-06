<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Venue;
use App\Helpers\GeohashHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ZKProximityFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the hardware secret key config used by the service
        config(['app.key' => 'fwber-zk-hardware-enclave-secret']);
    }

    public function test_valid_zk_proximity_proof_is_accepted()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a target venue at specific coordinates
        $venue = Venue::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Calculate expected geohash for the venue
        $geohash = GeohashHelper::encode($venue->latitude, $venue->longitude, 6);
        $timestamp = time();

        // Generate HMAC signature mimicking frontend
        $signatureStr = $geohash . $timestamp . $venue->id;
        $signature = hash_hmac('sha256', $signatureStr, 'fwber-zk-hardware-enclave-secret');

        $payload = [
            'target_entity_type' => 'venue',
            'target_entity_id' => $venue->id,
            'proof_payload' => [
                'geohash' => $geohash,
                'signature' => $signature,
            ],
            'public_signals' => [
                'timestamp' => $timestamp,
                'target_entity_id' => $venue->id,
            ],
            'proof_hash' => $signature,
        ];

        $response = $this->postJson('/api/proximity/zk-verify', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Proximity Cryptographically Verified',
                     'verified' => true,
                 ]);

        $this->assertDatabaseHas('zk_proximity_proofs', [
            'user_id' => $user->id,
            'target_entity_type' => 'venue',
            'target_entity_id' => $venue->id,
            'proof_hash' => $signature,
            'is_verified' => 1,
        ]);
    }

    public function test_zk_proof_rejected_with_invalid_signature()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $venue = Venue::factory()->create(['latitude' => 40.7128, 'longitude' => -74.0060]);
        $geohash = GeohashHelper::encode($venue->latitude, $venue->longitude, 6);
        $timestamp = time();

        $payload = [
            'target_entity_type' => 'venue',
            'target_entity_id' => $venue->id,
            'proof_payload' => [
                'geohash' => $geohash,
                'signature' => 'invalid-tampered-signature-123',
            ],
            'public_signals' => [
                'timestamp' => $timestamp,
                'target_entity_id' => $venue->id,
            ],
            'proof_hash' => 'invalid-tampered-signature-123',
        ];

        $response = $this->postJson('/api/proximity/zk-verify', $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'verified' => false,
                 ]);
                 
        $this->assertDatabaseMissing('zk_proximity_proofs', [
            'user_id' => $user->id,
        ]);
    }

    public function test_zk_proof_rejected_with_bad_geohash()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Target venue is in New York
        $venue = Venue::factory()->create(['latitude' => 40.7128, 'longitude' => -74.0060]);
        
        // User claims to be in London
        $londonGeohash = GeohashHelper::encode(51.5074, -0.1278, 6);
        $timestamp = time();

        // Sign the malicious geohash correctly (simulating a spoofed client)
        $signatureStr = $londonGeohash . $timestamp . $venue->id;
        $signature = hash_hmac('sha256', $signatureStr, 'fwber-zk-hardware-enclave-secret');

        $payload = [
            'target_entity_type' => 'venue',
            'target_entity_id' => $venue->id,
            'proof_payload' => [
                'geohash' => $londonGeohash,
                'signature' => $signature,
            ],
            'public_signals' => [
                'timestamp' => $timestamp,
                'target_entity_id' => $venue->id,
            ],
            'proof_hash' => $signature,
        ];

        $response = $this->postJson('/api/proximity/zk-verify', $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'verified' => false,
                 ]);
    }

    public function test_zk_proof_rejected_with_stale_timestamp_replay()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $venue = Venue::factory()->create(['latitude' => 40.7128, 'longitude' => -74.0060]);
        $geohash = GeohashHelper::encode($venue->latitude, $venue->longitude, 6);
        
        // Timestamp from 1 hour ago
        $timestamp = time() - 3600;

        $signatureStr = $geohash . $timestamp . $venue->id;
        $signature = hash_hmac('sha256', $signatureStr, 'fwber-zk-hardware-enclave-secret');

        $payload = [
            'target_entity_type' => 'venue',
            'target_entity_id' => $venue->id,
            'proof_payload' => [
                'geohash' => $geohash,
                'signature' => $signature,
            ],
            'public_signals' => [
                'timestamp' => $timestamp,
                'target_entity_id' => $venue->id,
            ],
            'proof_hash' => $signature,
        ];

        $response = $this->postJson('/api/proximity/zk-verify', $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'verified' => false,
                 ]);
    }
}
