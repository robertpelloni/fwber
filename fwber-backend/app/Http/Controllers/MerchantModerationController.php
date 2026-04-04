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

        $merchants->setCollection(
            $merchants->getCollection()
                ->map(function (MerchantProfile $merchant) {
                    $trust = $this->merchantTrustService->calculate($merchant);
                    $priorityScore = $this->calculatePriorityScore($merchant, $trust['trust_score']);

                    return [
                        ...$merchant->toArray(),
                        'user' => $merchant->user?->only(['id', 'name', 'email']),
                        ...$trust,
                        'priority_score' => $priorityScore,
                        'priority_tier' => match (true) {
                            $priorityScore >= 75 => 'urgent',
                            $priorityScore >= 50 => 'high',
                            $priorityScore >= 25 => 'normal',
                            default => 'low',
                        },
                    ];
                })
                ->sortByDesc('priority_score')
                ->values()
        );

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

        $freshMerchant = $merchant->fresh();

        return response()->json([
            'message' => 'Merchant verification updated successfully.',
            'merchant' => [
                ...$freshMerchant->toArray(),
                ...$this->merchantTrustService->calculate($freshMerchant),
            ],
        ]);
    }

    protected function calculatePriorityScore(MerchantProfile $merchant, int $trustScore): int
    {
        $pendingBonus = $merchant->verification_status === 'pending' ? 35 : 0;
        $commerceSignal = min(25, ((int) ($merchant->successful_orders_count ?? 0) * 5) + ((int) ($merchant->inventories_count ?? 0) * 2));
        $profileSignal = 0;

        if (filled($merchant->description)) {
            $profileSignal += 5;
        }
        if (filled($merchant->location_name) || filled($merchant->address)) {
            $profileSignal += 5;
        }

        $penalty = $merchant->verification_status === 'rejected' ? 20 : 0;

        return max(0, min(100, (int) round($pendingBonus + $commerceSignal + $profileSignal + ($trustScore * 0.35) - $penalty)));
    }
}
