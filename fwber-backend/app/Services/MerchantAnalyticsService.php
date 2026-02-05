<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MerchantProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantAnalyticsService
{
    public function getKPIs(MerchantProfile $merchant, string $range): array
    {
        // Placeholder logic - replacing static mock with dynamic db queries in next step
        // For now, returning structure compatible with frontend
        return [
            'kFactor' => 1.2,
            'totalReach' => 1500,
            'conversionRate' => 3.5,
            'totalRevenue' => 5000,
            'revenueChange' => 5.0,
        ];
    }

    public function getRetention(MerchantProfile $merchant, string $range): array
    {
        return [
            ['label' => 'Day 1', 'value' => 100, 'previousValue' => 100],
            ['label' => 'Day 7', 'value' => 60, 'previousValue' => 55],
            ['label' => 'Day 30', 'value' => 30, 'previousValue' => 25],
        ];
    }

    public function getPromotionsPerformance(MerchantProfile $merchant, string $range): array
    {
        // Mock data structure, will connect to real relations
        return [];
    }
}
