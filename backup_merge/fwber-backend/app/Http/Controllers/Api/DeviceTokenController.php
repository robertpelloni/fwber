<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/device-tokens",
     *     summary="Register a new device token",
     *     tags={"Push Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="The device token"),
     *             @OA\Property(property="type", type="string", description="The device type (e.g., 'web', 'ios', 'android')")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Device token registered successfully"
     *     )
     * )
     */
    public function store(StoreDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => Auth::id(),
                'type' => $validated['type'] ?? null,
            ]
        );

        return response()->json(['message' => 'Device token registered successfully'], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/device-tokens/{token}",
     *     summary="Delete a device token",
     *     tags={"Push Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Device token deleted successfully"
     *     )
     * )
     */
    public function destroy($token)
    {
        DeviceToken::where('token', $token)->where('user_id', Auth::id())->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get all notifications for the authenticated user",
     *     tags={"Push Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of notifications"
     *     )
     * )
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }
}
