<?php

namespace App\Http\Controllers;

use App\Models\InventoryRedemption;
use App\Models\MerchantInventory;
use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MerchantController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $profile = MerchantProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated + ['verification_status' => 'pending']
        );

        if ($user->role !== 'merchant') {
            $user->forceFill(['role' => 'merchant'])->save();
        }

        return response()->json([
            'message' => 'Merchant profile created successfully',
            'profile' => $profile,
            'user' => $user->fresh()->load('merchantProfile'),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $profile = $request->user()->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        return response()->json(['profile' => $profile]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $profile = $request->user()->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $validated = $request->validate([
            'business_name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $profile->update($validated);

        return response()->json([
            'message' => 'Merchant profile updated successfully',
            'profile' => $profile->fresh(),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $profile = $request->user()->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $inventoryQuery = MerchantInventory::query()->where('merchant_profile_id', $profile->id);
        $inventoryIds = $inventoryQuery->pluck('id');

        $stats = [
            'inventory_count' => (clone $inventoryQuery)->count(),
            'active_items' => (clone $inventoryQuery)->where('is_available', true)->count(),
            'total_stock' => (int) (clone $inventoryQuery)->sum('stock_count'),
            'pending_redemptions' => Schema::hasTable('inventory_redemptions')
                ? InventoryRedemption::whereIn('merchant_inventory_id', $inventoryIds)->whereNull('redeemed_at')->count()
                : 0,
            'redeemed_count' => Schema::hasTable('inventory_redemptions')
                ? InventoryRedemption::whereIn('merchant_inventory_id', $inventoryIds)->whereNotNull('redeemed_at')->count()
                : 0,
            'gross_revenue' => Schema::hasTable('merchant_payments')
                ? (float) MerchantPayment::where('merchant_profile_id', $profile->id)->where('status', 'succeeded')->sum('amount')
                : 0,
        ];

        $recentInventory = MerchantInventory::where('merchant_profile_id', $profile->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentRedemptions = Schema::hasTable('inventory_redemptions')
            ? InventoryRedemption::with(['inventory', 'user'])
                ->whereIn('merchant_inventory_id', $inventoryIds)
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return response()->json([
            'profile' => $profile,
            'stats' => $stats,
            'recent_inventory' => $recentInventory,
            'recent_redemptions' => $recentRedemptions,
            'storefront_path' => '/marketplace/'.$profile->id,
        ]);
    }
}
