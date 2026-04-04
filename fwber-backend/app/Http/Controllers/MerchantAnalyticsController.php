<?php

namespace App\Http\Controllers;

use App\Models\InventoryRedemption;
use App\Models\MerchantInventory;
use App\Models\MerchantPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $range = $request->input('range', '30d');
        $startDate = $this->resolveRangeStart($range);

        $payments = MerchantPayment::query()
            ->where('merchant_profile_id', $merchant->id)
            ->when($startDate, fn ($query) => $query->where('created_at', '>=', $startDate));

        $inventoryIds = MerchantInventory::where('merchant_profile_id', $merchant->id)->pluck('id');
        $redemptions = InventoryRedemption::query()
            ->whereIn('merchant_inventory_id', $inventoryIds)
            ->when($startDate, fn ($query) => $query->where('created_at', '>=', $startDate));

        $grossRevenue = (float) (clone $payments)->where('status', 'succeeded')->sum('amount');
        $orders = (clone $payments)->where('status', 'succeeded')->count();
        $redeemed = (clone $redemptions)->whereNotNull('redeemed_at')->count();
        $created = (clone $redemptions)->count();

        $topItems = MerchantInventory::query()
            ->where('merchant_profile_id', $merchant->id)
            ->withCount(['redemptions as redemptions_count' => function ($query) use ($startDate) {
                if ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }
            }])
            ->orderByDesc('redemptions_count')
            ->limit(5)
            ->get(['id', 'name', 'price_usd', 'stock_count', 'is_available']);

        return response()->json([
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'orders' => $orders,
                'issued_redemptions' => $created,
                'redeemed_redemptions' => $redeemed,
                'redemption_rate' => $created > 0 ? round(($redeemed / $created) * 100, 1) : 0,
                'average_order_value' => $orders > 0 ? round($grossRevenue / $orders, 2) : 0,
            ],
            'top_items' => $topItems,
            'recent_payments' => (clone $payments)->latest()->limit(10)->get(),
            'recent_redemptions' => (clone $redemptions)->with(['inventory', 'user'])->latest()->limit(10)->get(),
        ]);
    }

    protected function resolveRangeStart(string $range): ?Carbon
    {
        return match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null,
        };
    }
}
