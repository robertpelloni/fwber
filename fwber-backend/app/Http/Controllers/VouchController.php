<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vouch;
use App\Notifications\PushMessage;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VouchController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * @OA\Post(
     *     path="/public/vouch",
     *     tags={"Viral"},
     *     summary="Submit a public vouch",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="referral_code", type="string"),
     *             @OA\Property(property="type", type="string", enum={"safe", "fun", "hot"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Vouch recorded")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string|exists:users,referral_code',
            'type' => 'required|string|in:safe,fun,hot',
        ]);

        $user = User::where('referral_code', $request->referral_code)->firstOrFail();

        $ip = $request->ip();

        // Simple rate limit: Prevent duplicate vouch of same type from same IP
        $exists = Vouch::where('to_user_id', $user->id)
            ->where('ip_address', $ip)
            ->where('type', $request->type)
            ->exists();

        if ($exists) {
            // We return 200 even if duplicate to avoid leaking info or confusing UI,
            // but maybe 429 is better for debugging.
            // Let's return success message but not create.
             return response()->json(['message' => 'Vouch recorded successfully.']);
        }

        Vouch::create([
            'to_user_id' => $user->id,
            'type' => $request->type,
            'ip_address' => $ip,
        ]);

        try {
            $vouchCount = $user->vouches()->count();
            $this->achievementService->checkAndUnlock($user, 'vouches_received', $vouchCount);
        } catch (\Exception $e) {
            // Ignore achievement check failures
        }

        try {
            $emoji = match($request->type) {
                'safe' => '🛡️',
                'fun' => '🎉',
                'hot' => '🔥',
                default => '👍',
            };

            $user->notify(new PushMessage(
                "New Vouch! $emoji",
                "Someone verified you as {$request->type}!",
                "/profile",
                "social"
            ));
        } catch (\Exception $e) {
            // Ignore notification failures
        }

        return response()->json(['message' => 'Vouch recorded successfully.']);
    }
}
