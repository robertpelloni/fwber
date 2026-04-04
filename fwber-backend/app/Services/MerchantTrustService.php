<?php

namespace App\Services;

use App\Models\InventoryRedemption;
use App\Models\MerchantPayment;
use App\Models\MerchantProfile;

/**
 * Calculates a compact trust score for restored merchant storefronts.
 *
 * The score intentionally favors explicit verification first, then profile
 * completeness and real commerce evidence. This gives us a useful ranking and
 * moderation signal without reintroducing the full historical reputation stack.
 */
class MerchantTrustService
{
    public function calculate(MerchantProfile $merchant): array
    {
        $inventoryCount = property_exists($merchant, 'inventories_count') || isset($merchant->inventories_count)
            ? (int) $merchant->inventories_count
            : (int) $merchant->inventories()->count();

        $successfulOrders = property_exists($merchant, 'successful_orders_count') || isset($merchant->successful_orders_count)
            ? (int) $merchant->successful_orders_count
            : (int) MerchantPayment::where('merchant_profile_id', $merchant->id)->where('status', 'succeeded')->count();

        $issuedRedemptions = property_exists($merchant, 'issued_redemptions_count') || isset($merchant->issued_redemptions_count)
            ? (int) $merchant->issued_redemptions_count
            : (int) InventoryRedemption::whereHas('inventory', fn ($query) => $query->where('merchant_profile_id', $merchant->id))->count();

        $redeemedRedemptions = property_exists($merchant, 'redeemed_redemptions_count') || isset($merchant->redeemed_redemptions_count)
            ? (int) $merchant->redeemed_redemptions_count
            : (int) InventoryRedemption::whereHas('inventory', fn ($query) => $query->where('merchant_profile_id', $merchant->id))->whereNotNull('redeemed_at')->count();

        $verificationScore = match ($merchant->verification_status) {
            'verified' => 35,
            'pending' => 10,
            'rejected' => 0,
            default => 5,
        };

        $profileCompleteness = 0;
        if (filled($merchant->description)) {
            $profileCompleteness += 5;
        }
        if (filled($merchant->address)) {
            $profileCompleteness += 5;
        }
        if (filled($merchant->location_name)) {
            $profileCompleteness += 5;
        }
        if ($merchant->latitude !== null && $merchant->longitude !== null) {
            $profileCompleteness += 5;
        }

        $catalogScore = min(15, $inventoryCount * 3);
        $orderScore = min(15, $successfulOrders * 3);
        $redemptionRate = $issuedRedemptions > 0 ? ($redeemedRedemptions / $issuedRedemptions) : 0.0;
        $redemptionScore = (int) round(min(15, $redemptionRate * 15));

        $score = max(0, min(100, $verificationScore + $profileCompleteness + $catalogScore + $orderScore + $redemptionScore));

        return [
            'trust_score' => $score,
            'trust_tier' => match (true) {
                $score >= 80 => 'trusted',
                $score >= 55 => 'established',
                $score >= 30 => 'emerging',
                default => 'new',
            },
            'trust_breakdown' => [
                'verification' => $verificationScore,
                'profile_completeness' => $profileCompleteness,
                'catalog_depth' => $catalogScore,
                'successful_orders' => $orderScore,
                'redemption_reliability' => $redemptionScore,
                'issued_redemptions' => $issuedRedemptions,
                'redeemed_redemptions' => $redeemedRedemptions,
            ],
        ];
    }
}
