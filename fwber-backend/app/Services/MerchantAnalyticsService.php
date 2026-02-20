<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MerchantPayment;
use App\Models\MerchantProfile;
use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantAnalyticsService
{
    public function getKPIs(MerchantProfile $merchant, string $range): array
    {
        $startDate = $this->getStartDate($range);
        $previousStartDate = $this->getPreviousStartDate($range);

        // Revenue (from MerchantPayment)
        $totalRevenue = MerchantPayment::where('merchant_id', $merchant->user_id)
            ->where('status', 'paid') // Changed from 'completed' to 'paid' based on controller logic
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $previousRevenue = MerchantPayment::where('merchant_id', $merchant->user_id)
            ->where('status', 'paid')
            ->whereBetween('created_at', [$previousStartDate, $startDate])
            ->sum('amount');

        $revenueChange = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        // Reach (Unique users who viewed promotions)
        // Using PromotionEvent 'view' type
        $currentReach = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->where('type', 'view')
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id') // Count unique logged-in users. For guests, we might need session_id tracking later.
            ->count('user_id');
            
        // Fallback for MVP if low data: use total views
        $totalViews = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->where('type', 'view')
            ->where('created_at', '>=', $startDate)
            ->count();
            
        // Use total views as proxy for reach if unique users is too low (e.g. mostly guests)
        $totalReach = $currentReach > 0 ? $currentReach : $totalViews;


        // K-Factor (Viral Coefficient)
        // Calculated as: (Total Invites / Active Users) * Conversion Rate
        // For now, we use a simplified proxy: Shared Promotions / Unique Viewers
        $totalShares = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->where('type', 'share')
            ->where('created_at', '>=', $startDate)
            ->count();

        $kFactor = $totalReach > 0 ? round($totalShares / $totalReach, 2) : 0;

        // Conversion Rate (Redemptions / Reach)
        // Redemptions are tracked in PromotionEvent or Promotion aggregate
        $totalRedemptions = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->where('type', 'redemption')
            ->where('created_at', '>=', $startDate)
            ->count();

        $conversionRate = $totalReach > 0 ? ($totalRedemptions / $totalReach) * 100 : 0;

        return [
            'kFactor' => $kFactor,
            'totalReach' => $totalReach,
            'conversionRate' => round($conversionRate, 2),
            'totalRevenue' => $totalRevenue,
            'revenueChange' => round($revenueChange, 1),
        ];
    }

    public function getRetention(MerchantProfile $merchant, string $range): array
    {
        // Calculate retention cohorts based on repeat interactions
        // We look for users who interacted in the current window AND a previous window
        $currentInteractors = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->distinct('user_id')
            ->pluck('user_id');

        $calculateRetention = function ($daysInfo) use ($merchant, $currentInteractors) {
            $pastInteractors = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                    $q->where('merchant_id', $merchant->id);
                })
                ->where('created_at', '<', Carbon::now()->subDays($daysInfo['ago']))
                ->where('created_at', '>=', Carbon::now()->subDays($daysInfo['ago'] + $daysInfo['window']))
                ->distinct('user_id')
                ->pluck('user_id');

            $countStart = $pastInteractors->count();
            // Users present in BOTH sets
            $retainedCount = $currentInteractors->intersect($pastInteractors)->count();

            // Calculate previous cohort retention
            $previousPastInteractors = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                    $q->where('merchant_id', $merchant->id);
                })
                ->where('created_at', '<', Carbon::now()->subDays($daysInfo['ago'] + $daysInfo['window']))
                ->where('created_at', '>=', Carbon::now()->subDays($daysInfo['ago'] + ($daysInfo['window'] * 2)))
                ->distinct('user_id')
                ->pluck('user_id');

            $previousCurrentInteractors = \App\Models\PromotionEvent::whereHas('promotion', function ($q) use ($merchant) {
                    $q->where('merchant_id', $merchant->id);
                })
                ->where('created_at', '<', Carbon::now()->subDays($daysInfo['window']))
                ->where('created_at', '>=', Carbon::now()->subDays($daysInfo['window'] * 2))
                ->distinct('user_id')
                ->pluck('user_id');

            $previousCountStart = $previousPastInteractors->count();
            $previousRetainedCount = $previousCurrentInteractors->intersect($previousPastInteractors)->count();

            return [
                'label' => $daysInfo['label'],
                'value' => $countStart > 0 ? round(($retainedCount / $countStart) * 100) : 0,
                'previousValue' => $previousCountStart > 0 ? round(($previousRetainedCount / $previousCountStart) * 100) : 0,
            ];
        };

        return [
            $calculateRetention(['label' => 'Day 1', 'ago' => 1, 'window' => 1]),
            $calculateRetention(['label' => 'Day 7', 'ago' => 7, 'window' => 7]),
            $calculateRetention(['label' => 'Day 30', 'ago' => 30, 'window' => 30]),
            $calculateRetention(['label' => 'Day 90', 'ago' => 90, 'window' => 90]),
        ];
    }

    public function getPromotionsPerformance(MerchantProfile $merchant, string $range): array
    {
         $startDate = $this->getStartDate($range);

         $promotions = Promotion::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return $promotions->map(function ($promo) {
            // Calculate revenue from redemptions * cost
            // Ideally revenue should be tracked per event if dynamic
            $revenue = $promo->redemptions * ($promo->token_cost ?? 0);
            
            // Calculate real conversion rate
            $conversionRate = $promo->clicks > 0 ? ($promo->redemptions / $promo->clicks) * 100 : 0;

            return [
                'id' => $promo->id,
                'title' => $promo->title,
                'views' => $promo->views,
                'clicks' => $promo->clicks,
                'redemptions' => $promo->redemptions,
                'revenue' => $revenue,
                'conversionRate' => round($conversionRate, 1),
            ];
        })->toArray();
    }

    private function getStartDate(string $range): Carbon
    {
        return match ($range) {
            '24h' => Carbon::now()->subDay(),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };
    }

    private function getPreviousStartDate(string $range): Carbon
    {
        return match ($range) {
            '24h' => Carbon::now()->subDays(2),
            '30d' => Carbon::now()->subDays(60),
            '90d' => Carbon::now()->subDays(180),
            default => Carbon::now()->subDays(14),
        };
    }
}
