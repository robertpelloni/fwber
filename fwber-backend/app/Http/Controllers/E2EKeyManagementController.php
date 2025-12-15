<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\UserPublicKey;

class E2EKeyManagementController extends Controller
{
    /**
     * @OA\Post(
     *   path="/e2e/keys",
     *   tags={"E2E Encryption"},
     *   summary="Upload public key",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"public_key"},
     *       @OA\Property(property="public_key", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Key uploaded")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'public_key' => 'required|string',
            'key_type' => 'nullable|string',
            'device_id' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        UserPublicKey::updateOrCreate(
            ['user_id' => $user->id],
            [
                'public_key' => Crypt::encryptString($request->public_key),
                'key_type' => $request->input('key_type', 'ECDH'),
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
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Public key retrieved"),
     *   @OA\Response(response=404, description="User or key not found")
     * )
     */
    public function show($userId)
    {
        $key = UserPublicKey::where('user_id', $userId)->first();

        if (!$key) {
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
        $key = UserPublicKey::where('user_id', $user->id)->first();

        if (!$key) {
            return response()->json(['error' => 'Public key not found'], 404);
        }

        return response()->json([
            'public_key' => Crypt::decryptString($key->public_key),
            'key_type' => $key->key_type,
            'device_id' => $key->device_id,
        ]);
    }
}
