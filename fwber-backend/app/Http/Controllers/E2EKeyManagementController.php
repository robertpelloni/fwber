<?php

namespace App\Http\Controllers;

use App\Http\Requests\Security\StoreE2EKeyRequest;
use App\Models\User;
use App\Models\UserPublicKey;
use App\Models\E2EKeyBackup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class E2EKeyManagementController extends Controller
{
    /**
     * @OA\Post(
     *   path="/e2e/keys",
     *   tags={"E2E Encryption"},
     *   summary="Upload public key",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *     required=true,
     *
     *     @OA\JsonContent(
     *       required={"public_key"},
     *
     *       @OA\Property(property="public_key", type="string")
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Key uploaded")
     * )
     */
    public function store(StoreE2EKeyRequest $request)
    {
        $user = Auth::user();

        UserPublicKey::updateOrCreate(
            ['user_id' => $user->id, 'key_type' => $request->input('key_type', 'ECDH')],
            [
                'public_key' => Crypt::encryptString($request->public_key),
                'device_id' => $request->input('device_id'),
                'last_rotated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Public key stored successfully.']);
    }

    /**
     * @OA\Get(
     *   path="/e2e/keys/{userId}",
     *   tags={"E2E Encryption"},
     *   summary="Get user's public key",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="Public key retrieved"),
     *   @OA\Response(response=404, description="User or key not found")
     * )
     */
    public function show($userId)
    {
        $key = UserPublicKey::where('user_id', $userId)
            ->where('key_type', 'ECDH')
            ->first();

        if (! $key) {
            return response()->json(['error' => 'Public key not found for this user'], 404);
        }

        return response()->json([
            'user_id' => $key->user_id,
            'public_key' => Crypt::decryptString($key->public_key),
            'key_type' => $key->key_type,
            'device_id' => $key->device_id,
        ]);
    }

    public function me()
    {
        $user = Auth::user();
        $key = UserPublicKey::where('user_id', $user->id)
            ->where('key_type', 'ECDH')
            ->first();

        if (! $key) {
            return response()->json(['error' => 'Public key not found'], 404);
        }

        return response()->json([
            'public_key' => Crypt::decryptString($key->public_key),
            'key_type' => $key->key_type,
            'device_id' => $key->device_id,
        ]);
    }

    /**
     * Store a user-encrypted private key backup.
     */
    public function backup(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'key_type' => 'required|string|in:ecdh,rsa',
            'encrypted_private_key' => 'required|string',
            'salt' => 'required|string',
            'iv' => 'required|string',
        ]);

        E2EKeyBackup::updateOrCreate(
            ['user_id' => $user->id, 'key_type' => $validated['key_type']],
            [
                'encrypted_private_key' => $validated['encrypted_private_key'],
                'salt' => $validated['salt'],
                'iv' => $validated['iv'],
            ]
        );

        return response()->json(['message' => 'Key backup stored successfully.']);
    }

    /**
     * Retrieve a user-encrypted private key backup.
     */
    public function restore(Request $request)
    {
        $user = Auth::user();
        $keyType = $request->query('key_type', 'ecdh');

        $backup = E2EKeyBackup::where('user_id', $user->id)
            ->where('key_type', $keyType)
            ->first();

        if (!$backup) {
            return response()->json(['error' => 'No backup found for this key type.'], 404);
        }

        return response()->json([
            'encrypted_private_key' => $backup->encrypted_private_key,
            'salt' => $backup->salt,
            'iv' => $backup->iv,
        ]);
    }
}
