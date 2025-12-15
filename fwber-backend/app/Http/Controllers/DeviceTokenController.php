<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    /**
     * @OA\Post(
     *   path="/device-tokens",
     *   tags={"Notifications"},
     *   summary="Register a device token for push notifications",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"token", "platform"},
     *       @OA\Property(property="token", type="string"),
     *       @OA\Property(property="platform", type="string", enum={"ios", "android", "web"})
     *     )
     *   ),
     *   @OA\Response(response=200, description="Token registered")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'token' => $request->token,
            ],
            [
                'platform' => $request->platform,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device token registered']);
    }

    /**
     * @OA\Delete(
     *   path="/device-tokens/{token}",
     *   tags={"Notifications"},
     *   summary="Remove a device token",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Token removed")
     * )
     */
    public function destroy($token)
    {
        DeviceToken::where('user_id', Auth::id())
            ->where('token', $token)
            ->delete();

        return response()->json(['message' => 'Device token removed']);
    }
}
