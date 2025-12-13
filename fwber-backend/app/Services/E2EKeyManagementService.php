<?php

namespace App\Services;

use App\Models\UserPublicKey;
use Illuminate\Support\Facades\Crypt;

class E2EKeyManagementService
{
    /**
     * Store or update a user's public key.
     * The key is encrypted at rest using the app's encryption key.
     */
    public function storePublicKey(int $userId, string $publicKey, string $keyType = 'ECDH', ?string $deviceId = null): UserPublicKey
    {
        // Encrypt the public key before storage
        $encryptedKey = Crypt::encryptString($publicKey);

        return UserPublicKey::updateOrCreate(
            ['user_id' => $userId],
            [
                'public_key' => $encryptedKey,
                'key_type' => $keyType,
                'device_id' => $deviceId,
                'last_rotated_at' => now(),
            ]
        );
    }

    /**
     * Retrieve a user's public key.
     */
    public function getPublicKey(int $userId): ?string
    {
        $record = UserPublicKey::where('user_id', $userId)->first();

        if (!$record) {
            return null;
        }

        try {
            return Crypt::decryptString($record->public_key);
        } catch (\Exception $e) {
            // Handle decryption failure (e.g., key rotation)
            return null;
        }
    }

    /**
     * Delete a user's public key (e.g., on logout or device removal).
     */
    public function deletePublicKey(int $userId): void
    {
        UserPublicKey::where('user_id', $userId)->delete();
    }
}
