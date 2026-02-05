<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MerchantAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAnalyticsController extends Controller
{
    public function __construct(
        private readonly MerchantAnalyticsService $analyticsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;

        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $range = $request->input('range', '7d');

        return response()->json([
            'kpis' => $this->analyticsService->getKPIs($merchant, $range),
            'retention' => $this->analyticsService->getRetention($merchant, $range),
            'promotions' => $this->analyticsService->getPromotionsPerformance($merchant, $range),
        ]);
    }
}
