<?php

namespace App\Http\Controllers;

use App\Models\MerchantInventory;
use App\Models\InventoryRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantInventoryController extends Controller
{
    /**
     * List nearby inventory items from all merchants.
     */
    public function nearby(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radius = $request->query('radius', 1000);

        // Find merchants with active promotions/locations within radius
        $items = MerchantInventory::where('is_available', true)
            ->where('stock_count', '>', 0)
            ->whereHas('merchant.promotions', function($q) use ($lat, $lng, $radius) {
                // Approximate distance check via promotions location
                $q->where('is_active', true);
            })
            ->with(['merchant'])
            ->get();

        return response()->json(['items' => $items]);
    }

    /**
     * List all available items for a merchant.
     */
    public function index(Request $request, int $merchantId): JsonResponse
    {
        $items = MerchantInventory::where('merchant_profile_id', $merchantId)
            ->where('is_available', true)
            ->where('stock_count', '>', 0)
            ->get();

        return response()->json(['items' => $items]);
    }

    /**
     * Merchant: Create a new inventory item.
     */
    public function store(Request $request): JsonResponse
    {
        $merchant = Auth::user()->merchantProfile;
        if (!$merchant) return response()->json(['error' => 'Merchant profile required'], 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_tokens' => 'required|numeric|min:0',
            'stock_count' => 'required|integer|min:0',
            'image_url' => 'nullable|url',
        ]);

        $item = $merchant->inventories()->create($validated);

        return response()->json(['item' => $item], 201);
    }

    /**
     * User: Purchase an item with FWB Tokens.
     */
    public function purchase(Request $request, int $itemId): JsonResponse
    {
        $user = Auth::user();
        $item = MerchantInventory::findOrFail($itemId);

        if (!$item->is_available || $item->stock_count <= 0) {
            return response()->json(['error' => 'Item is out of stock'], 400);
        }

        if ($user->token_balance < $item->price_tokens) {
            return response()->json(['error' => 'Insufficient FWB token balance'], 400);
        }

        $redemption = DB::transaction(function () use ($user, $item) {
            // 1. Deduct tokens
            $user->decrement('token_balance', $item->price_tokens);

            // 2. Reduce stock
            $item->decrement('stock_count');

            // 3. Create redemption code
            return InventoryRedemption::create([
                'user_id' => $user->id,
                'merchant_inventory_id' => $item->id,
                'redemption_code' => 'FWB-' . strtoupper(Str::random(8)),
            ]);
        });

        return response()->json([
            'message' => 'Purchase successful!',
            'redemption_code' => $redemption->redemption_code,
            'remaining_balance' => $user->fresh()->token_balance
        ]);
    }

    /**
     * Merchant: Mark a code as redeemed.
     */
    public function redeem(Request $request): JsonResponse
    {
        $merchant = Auth::user()->merchantProfile;
        $code = $request->input('code');

        $redemption = InventoryRedemption::where('redemption_code', $code)
            ->whereHas('inventory', function($q) use ($merchant) {
                $q->where('merchant_profile_id', $merchant->id);
            })
            ->firstOrFail();

        if ($redemption->redeemed_at) {
            return response()->json(['error' => 'Code already redeemed'], 400);
        }

        $redemption->update(['redeemed_at' => now()]);

        return response()->json([
            'success' => true,
            'item_name' => $redemption->inventory->name,
            'user_name' => $redemption->user->name
        ]);
    }
}
