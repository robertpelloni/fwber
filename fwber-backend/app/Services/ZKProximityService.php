<?php

namespace App\Services;

use App\Models\ZkProximityProof;
use Illuminate\Support\Facades\Log;

class ZKProximityService
{
    /**
     * Mock verification of a SNARK/STARK circuit proof.
     * In a production environment, this would interface with a groth16/plonk verifier.
     */
    public function verifyProof(array $proofPayload, array $publicSignals, string $targetEntityType): bool
    {
        // 1. Validate Timestamp (Prevent Replay Attacks - 5 minute window)
        $timestamp = $publicSignals['timestamp'] ?? 0;
        if (abs(time() - $timestamp) > 300) {
            Log::warning('ZK proof rejected: Stale timestamp', ['timestamp' => $timestamp]);
            return false;
        }

        // 2. Validate Proof Signature (HMAC)
        // The frontend generates: HMAC-SHA256(geohash + timestamp + target_entity_id, APP_KEY)
        $expectedSignature = hash_hmac('sha256', 
            $proofPayload['geohash'] . $timestamp . $publicSignals['target_entity_id'], 
            'fwber-zk-hardware-enclave-secret'
        );

        if (!hash_equals($expectedSignature, $proofPayload['signature'])) {
            Log::warning('ZK proof rejected: Invalid signature');
            return false;
        }

        // 3. Validate Proximity (Geohash boundary check)
        // In a real ZK app, the circuit verifies the distance constraint without revealing the user's geohash.
        // Here, we simulate that circuit. We need the target entity's location.
        $targetLat = null;
        $targetLon = null;

        if ($targetEntityType === 'user') {
            $targetUser = \App\Models\User::with('profile')->find($publicSignals['target_entity_id']);
            if ($targetUser && $targetUser->profile) {
                $targetLat = $targetUser->profile->location_latitude;
                $targetLon = $targetUser->profile->location_longitude;
            }
        } elseif ($targetEntityType === 'venue') {
            $venue = \App\Models\Venue::find($publicSignals['target_entity_id']);
            if ($venue) {
                $targetLat = $venue->latitude;
                $targetLon = $venue->longitude;
            }
        }

        if (!$targetLat || !$targetLon) {
            Log::warning('ZK proof rejected: Target entity location unknown');
            return false;
        }

        // Calculate expected target geohash (precision 6 is approx 1.2km x 600m)
        $targetGeohash = \App\Helpers\GeohashHelper::encode($targetLat, $targetLon, 6);
        $userGeohash = substr($proofPayload['geohash'], 0, 6);

        // Allow adjacent cells - for simplicity we just check exact match or very close prefix
        if ($targetGeohash !== $userGeohash && substr($targetGeohash, 0, 5) !== substr($userGeohash, 0, 5)) {
             Log::warning('ZK proof rejected: Proximity constraint violation', [
                 'target' => $targetGeohash, 
                 'user' => $userGeohash
             ]);
             return false;
        }

        Log::info('ZK Proximity Proof successfully validated.');
        return true;
    }

    /**
     * Store the validated proof for the requesting user against the target entity
     */
    public function recordVerifiedPresence(int $userId, string $entityType, int $entityId, string $proofHash): ZkProximityProof
    {
        return ZkProximityProof::create([
            'user_id' => $userId,
            'target_entity_type' => $entityType,
            'target_entity_id' => $entityId,
            'proof_hash' => $proofHash,
            'is_verified' => true
        ]);
    }
}
