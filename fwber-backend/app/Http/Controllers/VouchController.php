<?php

namespace App\Http\Controllers;

use App\Http\Requests\Social\StoreVouchRequest;
use App\Models\User;
use App\Models\Vouch;
use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class VouchController extends Controller
{
    public function __construct(private readonly ReferralCommissionService $referralCommissionService)
    {
    }

    /**
     * Generate a stable referral-code-based vouch link for the signed-in user.
     */
    public function generateLink(Request $request): JsonResponse
    {
        $user = $this->referralCommissionService->ensureReferralCode($request->user());
        $url = rtrim((string) config('referrals.frontend_url', config('app.frontend_url', 'https://fwber.me')), '/').'/vouch/'.$user->referral_code;

        return response()->json([
            'url' => $url,
            'referral_code' => $user->referral_code,
        ]);
    }

    /**
     * Submit a public vouch using the owner's referral code.
     *
     * The frontend and shared links already use referral-code URLs, so the
     * controller accepts that shape directly instead of forcing callers to know
     * internal numeric user ids.
     */
    public function store(StoreVouchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('referral_code', $validated['referral_code'])->firstOrFail();
        $ip = $request->ip();

        $exists = Vouch::query()
            ->where('to_user_id', $user->id)
            ->where('ip_address', $ip)
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vouch recorded successfully.']);
        }

        $payload = [
            'to_user_id' => $user->id,
            'type' => $validated['type'],
            'ip_address' => $ip,
        ];

        if (Schema::hasColumn('vouches', 'relationship_type')) {
            $payload['relationship_type'] = $validated['relationship_type'] ?? 'friend';
        }
        if (Schema::hasColumn('vouches', 'comment')) {
            $payload['comment'] = $validated['comment'] ?? '';
        }
        if (Schema::hasColumn('vouches', 'voucher_name')) {
            $payload['voucher_name'] = $validated['voucher_name'] ?? 'Someone';
        }

        Vouch::query()->create($payload);

        return response()->json(['message' => 'Vouch recorded successfully.']);
    }
}
