<?php

namespace App\Http\Controllers;

use App\Models\MerchantProfile;
use App\Models\ModerationAction;
use App\Services\MerchantTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantModerationController extends Controller
{
    public function __construct(
        protected MerchantTrustService $merchantTrustService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $merchants = MerchantProfile::query()
            ->with('user')
            ->withCount('inventories')
            ->withCount(['payments as successful_orders_count' => fn ($query) => $query->where('status', 'succeeded')])
            ->withCount(['payments as total_orders_count'])
            ->when($status !== '', fn ($query) => $query->where('verification_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('business_name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("case when verification_status = 'pending' then 0 when verification_status = 'rejected' then 1 else 2 end")
            ->orderByDesc('updated_at')
            ->paginate(20);

        $merchants->getCollection()->transform(function (MerchantProfile $merchant) {
            $trust = $this->merchantTrustService->calculate($merchant);

            return [
                ...$merchant->toArray(),
                'user' => $merchant->user?->only(['id', 'name', 'email']),
                ...$trust,
            ];
        });

        return response()->json($merchants);
    }

    public function review(Request $request, int $merchantId): JsonResponse
    {
        if (! $request->user()?->is_moderator) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'verification_status' => ['required', 'string', 'in:pending,verified,rejected'],
            'verification_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $merchant = MerchantProfile::withCount('inventories')->findOrFail($merchantId);
        $merchant->update([
            'verification_status' => $validated['verification_status'],
            'verification_notes' => $validated['verification_notes'] ?? null,
            'verified_at' => $validated['verification_status'] === 'verified' ? now() : null,
            'verified_by' => $request->user()->id,
        ]);

        ModerationAction::create([
            'moderator_id' => $request->user()->id,
            'target_user_id' => $merchant->user_id,
            'action_type' => 'merchant_verification_'.$validated['verification_status'],
            'reason' => $validated['verification_notes'] ?? 'Merchant verification review updated',
            'metadata' => [
                'merchant_profile_id' => $merchant->id,
                'verification_status' => $validated['verification_status'],
            ],
        ]);

        return response()->json([
            'message' => 'Merchant verification updated successfully.',
            'merchant' => [
                ...$merchant->fresh()->toArray(),
                ...$this->merchantTrustService->calculate($merchant->fresh()),
            ],
        ]);
    }
}
