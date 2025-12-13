<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\E2EKeyManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class E2EKeyManagementController extends Controller
{
    protected $keyService;

    public function __construct(E2EKeyManagementService $keyService)
    {
        $this->keyService = $keyService;
    }

    /**
     * Store the authenticated user's public key.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'public_key' => 'required|string',
            'key_type' => 'nullable|string|in:ECDH,RSA',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $key = $this->keyService->storePublicKey(
            Auth::id(),
            $request->public_key,
            $request->key_type ?? 'ECDH',
            $request->device_id
        );

        return response()->json([
            'message' => 'Public key stored successfully.',
            'data' => [
                'key_type' => $key->key_type,
                'updated_at' => $key->updated_at,
            ]
        ], 200);
    }

    /**
     * Get a specific user's public key.
     */
    public function show($userId)
    {
        $publicKey = $this->keyService->getPublicKey($userId);

        if (!$publicKey) {
            return response()->json(['message' => 'Public key not found for this user.'], 404);
        }

        return response()->json([
            'user_id' => $userId,
            'public_key' => $publicKey,
        ]);
    }

    /**
     * Get the authenticated user's own public key.
     */
    public function me()
    {
        $publicKey = $this->keyService->getPublicKey(Auth::id());

        if (!$publicKey) {
            return response()->json(['message' => 'No public key stored.'], 404);
        }

        return response()->json([
            'public_key' => $publicKey,
        ]);
    }
}
