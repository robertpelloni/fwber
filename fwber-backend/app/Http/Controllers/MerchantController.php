<?php

namespace App\Http\Controllers;

use App\Models\MerchantProfile;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Requests\Merchant\RegisterMerchantRequest;
use App\Http\Requests\Merchant\UpdateMerchantProfileRequest;
use App\Http\Requests\Merchant\StorePromotionRequest;
use App\Http\Requests\Merchant\BrowseDealsRequest;
use App\Http\Requests\Merchant\TrackPromotionRequest;

class MerchantController extends Controller
{
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

        if (!$user->merchantProfile) {
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

        if (!$profile) {
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

        if (!$profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 403);
        }

        // In a real app, we might check: if ($profile->verification_status !== 'verified') ...

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

        if (!$profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $promotions = $profile->promotions()->orderBy('created_at', 'desc')->get();

        return response()->json($promotions);
    }

    /**
     * Consumer-facing: Browse active deals/promotions nearby.
     */
    public function browseDeals(BrowseDealsRequest $request)
    {
        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5000; // 5km default

        $query = Promotion::with(['merchant:id,business_name,category,address'])
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->withinBox($lat, $lng, $radius);

        // Filter by merchant category if provided
        if ($request->has('category')) {
            $query->whereHas('merchant', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        // Sort options
        $sort = $request->sort ?? 'distance';
        if ($sort === 'newest') {
            $query->orderByDesc('created_at');
        } elseif ($sort === 'expiring') {
            $query->orderBy('expires_at');
        } elseif ($sort === 'discount') {
            $query->orderByDesc('discount_value');
        }
        // Default: distance-based (withinBox already filters, but we could add raw distance calc)

        $deals = $query->paginate($request->per_page ?? 20);

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
}
