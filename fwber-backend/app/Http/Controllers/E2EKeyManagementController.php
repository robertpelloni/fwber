<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        ]);

        $user = Auth::user();
        $user->public_key = $request->public_key;
        $user->save();

        return response()->json(['message' => 'Public key updated']);
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
        $user = User::findOrFail($userId);

        if (!$user->public_key) {
            return response()->json(['error' => 'Public key not found for this user'], 404);
        }

        return response()->json(['public_key' => $user->public_key]);
    }
}
