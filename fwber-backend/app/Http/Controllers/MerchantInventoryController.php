<?php

namespace App\Http\Controllers;

use App\Models\InventoryRedemption;
use App\Models\MerchantInventory;
use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantInventoryController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
    ) {}

    public function nearby(Request $request): JsonResponse
    {
        $items = MerchantInventory::query()
            ->with('merchant')
            ->where('is_available', true)
            ->where('stock_count', '>', 0)
            ->latest()
            ->limit(24)
            ->get();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function showMarketplace(Request $request, int $merchantId): JsonResponse
    {
        $merchant = MerchantProfile::findOrFail($merchantId);
        $items = MerchantInventory::query()
            ->where('merchant_profile_id', $merchantId)
            ->where('is_available', true)
            ->where('stock_count', '>', 0)
            ->latest()
            ->get();

        return response()->json([
            'merchant' => $merchant,
            'items' => $items,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;

        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $items = MerchantInventory::query()
            ->where('merchant_profile_id', $merchant->id)
            ->withCount(['redemptions as pending_redemptions_count' => function ($query) {
                $query->whereNull('redeemed_at');
            }])
            ->latest()
            ->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile required.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_usd' => ['required', 'numeric', 'min:0'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $item = $merchant->inventories()->create($validated + [
            'is_available' => (bool) ($validated['is_available'] ?? true),
        ]);

        return response()->json([
            'message' => 'Inventory item created successfully',
            'item' => $item,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile required.'], 403);
        }

        $item = MerchantInventory::where('merchant_profile_id', $merchant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_usd' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_count' => ['sometimes', 'required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'item' => $item->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile required.'], 403);
        }

        $item = MerchantInventory::where('merchant_profile_id', $merchant->id)->findOrFail($id);
        $item->update(['is_available' => false]);

        return response()->json([
            'message' => 'Inventory item archived successfully',
            'item' => $item->fresh(),
        ]);
    }

    public function purchase(Request $request, int $itemId): JsonResponse
    {
        $user = $request->user();
        $paymentMethodId = $request->string('payment_method_id')->toString();
        $paymentIntentId = $request->string('payment_intent_id')->toString();
        $driver = config('services.payment.driver', 'mock');

        $item = MerchantInventory::with('merchant')->findOrFail($itemId);
        if (! $item->is_available || $item->stock_count < 1) {
            return response()->json(['message' => 'Item is out of stock.'], 422);
        }

        if ($item->merchant?->user_id === $user->id) {
            return response()->json(['message' => 'You cannot buy your own inventory item.'], 422);
        }

        if ($paymentIntentId !== '') {
            $result = $this->paymentGateway->verifyPayment($paymentIntentId);
        } elseif ($paymentMethodId !== '') {
            $result = $this->paymentGateway->charge((float) $item->price_usd, 'USD', $paymentMethodId, [
                'item_id' => (string) $item->id,
                'merchant_profile_id' => (string) $item->merchant_profile_id,
                'payer_id' => (string) $user->id,
                'description' => 'Marketplace purchase: '.$item->name,
            ]);
        } elseif ($driver === 'mock') {
            $result = $this->paymentGateway->charge((float) $item->price_usd, 'USD', 'tok_marketplace_mock', [
                'item_id' => (string) $item->id,
                'merchant_profile_id' => (string) $item->merchant_profile_id,
                'payer_id' => (string) $user->id,
                'description' => 'Marketplace purchase: '.$item->name,
            ]);
        } else {
            return response()->json(['message' => 'A live payment method is required for marketplace purchases.'], 422);
        }

        if (! $result->success) {
            return response()->json(['message' => $result->message ?: 'Payment failed.'], 400);
        }

        $payload = DB::transaction(function () use ($itemId, $user, $item, $result, $driver) {
            $lockedItem = MerchantInventory::lockForUpdate()->findOrFail($itemId);
            if (! $lockedItem->is_available || $lockedItem->stock_count < 1) {
                abort(422, 'Item is out of stock.');
            }

            $lockedItem->decrement('stock_count');

            $payment = MerchantPayment::create([
                'merchant_profile_id' => $lockedItem->merchant_profile_id,
                'merchant_inventory_id' => $lockedItem->id,
                'payer_id' => $user->id,
                'amount' => $lockedItem->price_usd,
                'currency' => 'USD',
                'payment_gateway' => $driver,
                'transaction_id' => $result->transactionId,
                'status' => 'succeeded',
                'description' => 'Marketplace purchase: '.$lockedItem->name,
                'metadata' => $result->data,
                'paid_at' => now(),
            ]);

            $redemption = InventoryRedemption::create([
                'user_id' => $user->id,
                'merchant_inventory_id' => $lockedItem->id,
                'merchant_payment_id' => $payment->id,
                'redemption_code' => 'FWB-'.strtoupper(Str::random(8)),
            ]);

            return [
                'payment' => $payment,
                'redemption' => $redemption,
                'item' => $lockedItem->fresh(),
            ];
        });

        return response()->json([
            'message' => 'Purchase successful!',
            'merchant_name' => $item->merchant?->business_name,
            'item_name' => $item->name,
            'redemption_code' => $payload['redemption']->redemption_code,
            'receipt' => [
                'id' => $payload['payment']->id,
                'amount' => $payload['payment']->amount,
                'currency' => $payload['payment']->currency,
                'status' => $payload['payment']->status,
                'paid_at' => $payload['payment']->paid_at,
            ],
            'remaining_stock' => $payload['item']->stock_count,
        ]);
    }

    public function redeem(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchantProfile;
        if (! $merchant) {
            return response()->json(['message' => 'Merchant profile required.'], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $redemption = InventoryRedemption::with(['inventory', 'user'])
            ->where('redemption_code', $validated['code'])
            ->whereHas('inventory', function ($query) use ($merchant) {
                $query->where('merchant_profile_id', $merchant->id);
            })
            ->firstOrFail();

        if ($redemption->redeemed_at) {
            return response()->json(['message' => 'Code already redeemed.'], 422);
        }

        $redemption->update(['redeemed_at' => now()]);

        return response()->json([
            'success' => true,
            'item_name' => $redemption->inventory?->name,
            'user_name' => $redemption->user?->name,
            'redeemed_at' => $redemption->redeemed_at,
        ]);
    }
}
