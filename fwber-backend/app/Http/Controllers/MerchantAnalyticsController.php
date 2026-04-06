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
            'broadcasts' => $this->analyticsService->getBroadcastHistory($merchant, $range),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/merchant-portal/analytics/export",
     *   tags={"Merchant Analytics"},
     *   summary="Export Merchant Data to CSV",
     *   description="Generates a CSV report of merchant promotions and broadcasts.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="CSV generated")
     * )
     */
    public function exportCsv(Request $request)
    {
        $merchant = $request->user()->merchantProfile;

        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $range = $request->input('range', '30d');
        $promotions = $this->analyticsService->getPromotionsPerformance($merchant, $range);

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=fwber_merchant_report.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Title', 'Views', 'Clicks', 'Redemptions', 'Revenue', 'Conversion Rate'];

        $callback = function() use($promotions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($promotions as $promo) {
                fputcsv($file, [
                    $promo['id'],
                    $promo['title'],
                    $promo['views'],
                    $promo['clicks'],
                    $promo['redemptions'],
                    $promo['revenue'],
                    $promo['conversionRate'] . '%'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
