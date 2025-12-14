<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OnboardingController extends Controller
{
    /**
     * Get the onboarding status of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'completed' => !is_null($user->onboarding_completed_at),
            'completed_at' => $user->onboarding_completed_at,
        ]);
    }

    /**
     * Mark the onboarding as completed for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->onboarding_completed_at)) {
            $user->onboarding_completed_at = now();
            $user->save();
        }

        return response()->json([
            'message' => 'Onboarding marked as completed.',
            'completed_at' => $user->onboarding_completed_at,
        ]);
    }
}
