<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public const TYPES = [
        'new_match' => 'New Matches',
        'new_message' => 'New Messages',
        'event_reminder' => 'Event Reminders',
        'event_invitation' => 'Event Invitations',
        'subscription_expired' => 'Subscription Alerts',
        'payment_failed' => 'Payment Alerts',
        'marketing' => 'Marketing & Updates',
    ];

    /**
     * @OA\Get(
     *     path="/api/notification-preferences",
     *     summary="Get user notification preferences",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of preferences",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="label", type="string"),
     *                 @OA\Property(property="mail", type="boolean"),
     *                 @OA\Property(property="push", type="boolean"),
     *                 @OA\Property(property="database", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences()->get()->keyBy('type');

        $result = [];
        foreach (self::TYPES as $type => $label) {
            $pref = $preferences->get($type);
            $result[] = [
                'type' => $type,
                'label' => $label,
                'mail' => $pref ? $pref->mail : true,
                'push' => $pref ? $pref->push : true,
                'database' => $pref ? $pref->database : true,
            ];
        }

        return response()->json($result);
    }

    /**
     * @OA\Put(
     *     path="/api/notification-preferences/{type}",
     *     summary="Update notification preference",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="mail", type="boolean"),
     *             @OA\Property(property="push", type="boolean"),
     *             @OA\Property(property="database", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preference updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="mail", type="boolean"),
     *             @OA\Property(property="push", type="boolean"),
     *             @OA\Property(property="database", type="boolean")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $type)
    {
        if (!array_key_exists($type, self::TYPES)) {
            return response()->json(['message' => 'Invalid notification type'], 400);
        }

        $validated = $request->validate([
            'mail' => 'boolean',
            'push' => 'boolean',
            'database' => 'boolean',
        ]);

        $preference = $request->user()->notificationPreferences()->updateOrCreate(
            ['type' => $type],
            $validated
        );

        return response()->json($preference);
    }
}
