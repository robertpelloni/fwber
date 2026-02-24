<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vouch;
use App\Notifications\PushMessage;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Social\StoreVouchRequest;
use Illuminate\Support\Facades\URL;

class VouchController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * @OA\Post(
     *     path="/vouch/generate-link",
     *     tags={"Viral"},
     *     summary="Generate a unique vouch link for the user",
     *     @OA\Response(response=200, description="Vouch link generated")
     * )
     */
    public function generateLink(Request $request): JsonResponse
    {
        $user = $request->user();
        // Generate a signed URL that expires in 7 days
        // This ensures the link is tied to this specific user and adds a layer of trust
        // We use 'vouch.create' as the named route on the frontend (conceptually), 
        // but here we just return the URL string.
        
        // Actually, for a simple public link, we can just use the referral code or ID.
        // But a signed URL is safer if we want to prevent enumeration.
        // Let's stick to referral_code for simplicity and shareability as established in other viral features.
        // However, the instructions mentioned "generateLink".
        
        $url = config('app.frontend_url') . '/vouch/' . $user->referral_code;

        return response()->json([
            'url' => $url,
            'referral_code' => $user->referral_code
        ]);
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
     *             @OA\Property(property="type", type="string", enum={"safe", "fun", "hot"}),
     *             @OA\Property(property="relationship_type", type="string"),
     *             @OA\Property(property="comment", type="string"),
     *             @OA\Property(property="voucher_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Vouch recorded")
     * )
     */
    public function store(StoreVouchRequest $request): JsonResponse
    {

        $user = User::where('referral_code', $request->referral_code)->firstOrFail();

        $ip = $request->ip();

        // Simple rate limit: Prevent duplicate vouch of same type from same IP
        $exists = Vouch::where('to_user_id', $user->id)
            ->where('ip_address', $ip)
            ->where('type', $request->type)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vouch recorded successfully.']);
        }

        Vouch::create([
            'to_user_id' => $user->id,
            'type' => $request->type,
            'relationship_type' => $request->relationship_type,
            'comment' => $request->comment,
            'voucher_name' => $request->voucher_name,
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

            $voucher = $request->voucher_name ?? 'Someone';
            $user->notify(new PushMessage(
                "New Vouch! $emoji",
                "$voucher vouched for you as {$request->type}!",
                "/profile",
                "social"
            ));
        } catch (\Exception $e) {
            // Ignore notification failures
        }

        return response()->json(['message' => 'Vouch recorded successfully.']);
    }
}
