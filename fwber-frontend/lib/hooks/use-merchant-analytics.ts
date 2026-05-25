import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/lib/api/client';
import { AnalyticsRange } from '@/lib/api/types';

interface KPIData {
    kFactor: number;
    totalReach: number;
    conversionRate: number;
    totalRevenue: number;
    revenueChange: number;
}

interface RetentionData {
    label: string;
    value: number;
    previousValue: number;
}

interface PromotionPerformance {
    id: string;
    title: string;
    views: number;
    clicks: number;
    redemptions: number;
    revenue: number;
    conversionRate: number;
}

interface BroadcastHistoryItem {
    id: number;
    content: string;
    created_at: string | null;
    expires_at: string | null;
    status: 'active' | 'expired';
    promo_code: string | null;
    vibe_target: string;
    vibe_snapshot: string | null;
    activity_score: number | null;
    promotion_id: number | null;
    promotion_title: string | null;
    visibility_radius_m: number;
}

interface MerchantAnalyticsResponse {
    kpis: KPIData;
    retention: RetentionData[];
    promotions: PromotionPerformance[];
    broadcasts: BroadcastHistoryItem[];
}

export function useMerchantAnalytics(range: AnalyticsRange) {
    return useQuery<MerchantAnalyticsResponse>({
        queryKey: ['merchant-analytics', range],
        queryFn: async () => {
            const response = await apiClient.get<MerchantAnalyticsResponse>('/merchant-portal/analytics', { params: { range } });
            return response.data;
        },
        // Keep generic placeholder data until backend is fully seeded in production
        placeholderData: (previousData) => previousData,
    });
}
