<?php

namespace App\Http\Controllers;

use App\Http\Requests\Merchant\BrowseDealsRequest;
use App\Http\Requests\Merchant\RegisterMerchantRequest;
use App\Http\Requests\Merchant\StorePromotionRequest;
use App\Http\Requests\Merchant\TrackPromotionRequest;
use App\Http\Requests\Merchant\UpdateMerchantProfileRequest;
use App\Http\Requests\Merchant\UpdatePromotionRequest;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Services\DealRankingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
{
    public function __construct(
        private readonly DealRankingService $dealRankingService,
    ) {
    }

    /**
     * Register the current user as a merchant.
     */
    public function register(RegisterMerchantRequest $request)
    {
        $user = Auth::user();

        if ($user->role === 'merchant' || $user->merchantProfile) {
            return response()->json(['message' => 'User is already a merchant.'], 400);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated) {
            // Update user role if not already set (though check above handles it partially)
            if ($user->role !== 'merchant') {
                $user->role = 'merchant';
                $user->save();
            }

            MerchantProfile::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'address' => $validated['address'] ?? null,
                'verification_status' => 'pending',
            ]);
        });

        return response()->json([
            'message' => 'Merchant profile created successfully.',
            'user' => $user->fresh()->load('merchantProfile'),
        ], 201);
    }

    /**
     * Get the current user's merchant profile.
     */
    public function getProfile()
    {
        $user = Auth::user();

        if (! $user->merchantProfile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        return response()->json($user->merchantProfile);
    }

    /**
     * Update the merchant profile.
     */
    public function updateProfile(UpdateMerchantProfileRequest $request)
    {
        $user = Auth::user();
        $profile = $user->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $validated = $request->validated();

        $profile->update($validated);

        return response()->json([
            'message' => 'Merchant profile updated.',
            'profile' => $profile,
        ]);
    }

    /**
     * Create a new promotion.
     */
    public function storePromotion(StorePromotionRequest $request)
    {
        $user = Auth::user();
        $profile = $user->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 403);
        }

        // Production Requirement: Merchant must be verified to post promotions
        if ($profile->verification_status !== 'verified') {
            return response()->json([
                'message' => 'Merchant verification required. Please ensure your business details are complete and contact support.',
                'status' => $profile->verification_status,
            ], 403);
        }

        $validated = $request->validated();

        $promotion = $profile->promotions()->create($validated);

        return response()->json([
            'message' => 'Promotion created successfully.',
            'promotion' => $promotion,
        ], 201);
    }

    /**
     * List merchant's promotions.
     */
    public function getPromotions()
    {
        $user = Auth::user();
        $profile = $user->merchantProfile;

        if (! $profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $promotions = $profile->promotions()->orderBy('created_at', 'desc')->get();

        return response()->json($promotions);
    }

    /**
     * Show a single merchant-owned promotion with interaction metrics.
     */
    public function showPromotion(int $promotionId)
    {
        $promotion = $this->resolveMerchantPromotion($promotionId);

        return response()->json([
            'promotion' => $promotion,
            'metrics' => [
                'views' => $promotion->views,
                'clicks' => $promotion->clicks,
                'redemptions' => $promotion->redemptions,
                'conversion_rate' => $promotion->views > 0
                    ? round(($promotion->redemptions / $promotion->views) * 100, 2)
                    : 0.0,
            ],
        ]);
    }

    /**
     * Update a single merchant-owned promotion.
     */
    public function updatePromotion(UpdatePromotionRequest $request, int $promotionId)
    {
        $promotion = $this->resolveMerchantPromotion($promotionId);
        $validated = $request->validated();

        if (array_key_exists('expires_at', $validated) && ! array_key_exists('starts_at', $validated)) {
            $request->merge(['starts_at' => $promotion->starts_at?->toDateTimeString()]);
            $validated = $request->validated();
            unset($validated['starts_at']);
        }

        $promotion->update($validated);

        return response()->json([
            'message' => 'Promotion updated successfully.',
            'promotion' => $promotion->fresh(),
        ]);
    }

    /**
     * Deactivate a merchant-owned promotion.
     */
    public function destroyPromotion(int $promotionId)
    {
        $promotion = $this->resolveMerchantPromotion($promotionId);

        $promotion->update([
            'is_active' => false,
            'expires_at' => now()->lt($promotion->expires_at) ? now() : $promotion->expires_at,
        ]);

        return response()->json([
            'message' => 'Promotion deactivated successfully.',
            'promotion' => $promotion->fresh(),
        ]);
    }

    /**
     * Consumer-facing: Browse active deals/promotions nearby.
     */
    public function browseDeals(BrowseDealsRequest $request)
    {
        $validated = $request->validated();
        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $radius = $validated['radius'] ?? 5000; // 5km default
        $rankingStrategy = $validated['ranking_strategy'] ?? 'distance';
        $perPage = $validated['per_page'] ?? 20;

        $query = Promotion::with(['merchant:id,user_id,business_name,category,description,address,verification_status'])
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->withinBox($lat, $lng, $radius);

        // Filter by merchant category if provided
        if (! empty($validated['category'])) {
            $query->whereHas('merchant', function ($q) use ($validated) {
                $q->where('category', $validated['category']);
            });
        }

        // Sort options
        $sort = $validated['sort'] ?? 'distance';
        if ($sort === 'newest') {
            $query->orderByDesc('created_at');
        } elseif ($sort === 'expiring') {
            $query->orderBy('expires_at');
        } elseif ($sort === 'discount') {
            $query->orderByDesc('discount_value');
        }
        // Default: distance-based (withinBox already filters, but we could add raw distance calc)

        $deals = $query->paginate($perPage);

        if ($rankingStrategy === 'trust-aware' && Auth::check()) {
            $rankedDeals = $this->dealRankingService->rankNearby(
                Auth::user(),
                $deals->getCollection(),
                $lat,
                $lng
            );

            $deals->setCollection($rankedDeals);
            $dealsArray = $deals->toArray();

            return response()->json(array_merge($dealsArray, [
                'deals' => $dealsArray['data'],
                'meta' => [
                    'ranking_strategy' => $this->dealRankingService->buildRankingStrategy(),
                ],
            ]));
        }

        return response()->json($deals);
    }

    /**
     * Get merchant categories for filtering.
     */
    public function getCategories()
    {
        $categories = MerchantProfile::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['categories' => $categories]);
    }

    /**
     * Track promotion interaction (View, Click, Redemption).
     */
    public function trackPromotion(TrackPromotionRequest $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        // Record the event
        \App\Models\PromotionEvent::create([
            'promotion_id' => $promotion->id,
            'user_id' => Auth::id(), // Nullable for guests
            'type' => $request->type,
            'metadata' => $request->metadata,
        ]);

        // Update aggregate counters
        if ($request->type === 'view') {
            $promotion->increment('views');
        } elseif ($request->type === 'click') {
            $promotion->increment('clicks');
        } elseif ($request->type === 'redemption') {
            $promotion->increment('redemptions');
        }

        return response()->json(['success' => true]);
    }

    private function resolveMerchantPromotion(int $promotionId): Promotion
    {
        $profile = Auth::user()?->merchantProfile;

        abort_if(! $profile, 404, 'Merchant profile not found.');

        return $profile->promotions()->findOrFail($promotionId);
    }
}
