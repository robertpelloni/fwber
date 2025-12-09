<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribePushRequest;
use App\Http\Requests\UnsubscribePushRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Subscribe the user to push notifications.
     *
     * @param  \App\Http\Requests\SubscribePushRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(SubscribePushRequest $request): JsonResponse
    {
        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = Auth::user();
        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true, 'message' => 'Push subscription updated.']);
    }

    /**
     * Unsubscribe the user from push notifications.
     *
     * @param  \App\Http\Requests\UnsubscribePushRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribe(UnsubscribePushRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true, 'message' => 'Push subscription deleted.']);
    }
}
