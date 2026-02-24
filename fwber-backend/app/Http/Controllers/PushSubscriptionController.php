<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Requests\Notification\UpdatePushSubscriptionRequest;
use App\Http\Requests\Notification\DestroyPushSubscriptionRequest;

class PushSubscriptionController extends Controller
{
    use ValidatesRequests;

    /**
     * Update user's subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePushSubscriptionRequest $request)
    {

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = $request->user();
        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true], 200);
    }

    /**
     * Delete the specified subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyPushSubscriptionRequest $request)
    {

        $request->user()->deletePushSubscription($request->endpoint);

        return response()->json(null, 204);
    }
}
