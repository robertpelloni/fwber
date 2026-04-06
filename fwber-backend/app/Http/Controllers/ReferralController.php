<?php

namespace App\Http\Controllers;

use App\Services\ReferralCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralCommissionService $referralCommissionService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json(
            $this->referralCommissionService->buildSummary($request->user())
        );
    }
}
