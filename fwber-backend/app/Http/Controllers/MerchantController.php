<?php

namespace App\Http\Controllers;

use App\Models\MerchantProfile;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MerchantController extends Controller
{
    /**
     * Register the current user as a merchant.
     */
    public function register(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'merchant' || $user->merchantProfile) {
            return response()->json(['message' => 'User is already a merchant.'], 400);
        }

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

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
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = $user->merchantProfile;

        if (!$profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 404);
        }

        $validated = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $profile->update($validated);

        return response()->json([
            'message' => 'Merchant profile updated.',
            'profile' => $profile,
        ]);
    }

    /**
     * Create a new promotion.
     */
    public function storePromotion(Request $request)
    {
        $user = Auth::user();
        $profile = $user->merchantProfile;

        if (!$profile) {
            return response()->json(['message' => 'Merchant profile not found.'], 403);
        }

        // In a real app, we might check: if ($profile->verification_status !== 'verified') ...

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'promo_code' => 'nullable|string|max:50',
            'discount_value' => 'required|string|max:50',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:5000',
            'token_cost' => 'integer|min:0',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
        ]);

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
}
