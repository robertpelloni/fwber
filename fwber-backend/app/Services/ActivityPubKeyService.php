<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPublicKey;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class ActivityPubKeyService
{
    public const KEY_TYPE = 'ActivityPubRSA';

    public const DEVICE_ID = 'activitypub-main-key';

    public function actorUri(User $user): string
    {
        if ($user->is_remote && is_string($user->actor_uri) && $user->actor_uri !== '') {
            return $user->actor_uri;
        }

        return url("/api/federation/users/{$user->id}");
    }

    public function keyId(User $user): string
    {
        return $this->actorUri($user).'#main-key';
    }

    public function getPublicKey(User $user): string
    {
        return $this->decryptKeyPart($this->ensureKeyPair($user)->public_key);
    }

    public function getPrivateKey(User $user): string
    {
        return $this->decryptKeyPart($this->ensureKeyPair($user)->private_key);
    }

    public function ensureKeyPair(User $user): UserPublicKey
    {
        $existingRecord = UserPublicKey::query()
            ->where('user_id', $user->id)
            ->where('key_type', self::KEY_TYPE)
            ->first();

        if ($existingRecord && is_string($existingRecord->public_key) && is_string($existingRecord->private_key)) {
            return $existingRecord;
        }

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($keyPair === false || ! openssl_pkey_export($keyPair, $privateKeyPem)) {
            throw new RuntimeException('Unable to generate ActivityPub key pair.');
        }

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyPem = $details['key'] ?? null;

        if (! is_string($publicKeyPem) || trim($publicKeyPem) === '') {
            throw new RuntimeException('Generated ActivityPub public key was empty.');
        }

        return UserPublicKey::updateOrCreate(
            [
                'user_id' => $user->id,
                'key_type' => self::KEY_TYPE,
            ],
            [
                'public_key' => Crypt::encryptString($publicKeyPem),
                'private_key' => Crypt::encryptString($privateKeyPem),
                'device_id' => self::DEVICE_ID,
                'last_rotated_at' => now(),
            ]
        );
    }

    protected function decryptKeyPart(?string $encryptedValue): string
    {
        if (! is_string($encryptedValue) || trim($encryptedValue) === '') {
            throw new RuntimeException('ActivityPub key material is missing.');
        }

        return Crypt::decryptString($encryptedValue);
    }
}
