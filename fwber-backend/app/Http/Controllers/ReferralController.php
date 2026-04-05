<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralCommissionService $referralCommissionService)
    {
    }

    /**
     * Public referral-code lookup used by registration banners, referral
     * landing pages, and the vouch flow.
     */
    public function lookup(string $code): JsonResponse
    {
        $referrer = User::query()
            ->where('referral_code', $code)
            ->first();

        if (! $referrer) {
            return response()->json([
                'valid' => false,
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer_name' => $referrer->name,
            'referrer_avatar' => $referrer->avatar_url,
            'has_golden_tickets' => (int) $referrer->golden_tickets_remaining > 0,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(
            $this->referralCommissionService->buildSummary($request->user())
        );
    }
}
