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

interface MerchantAnalyticsResponse {
    kpis: KPIData;
    retention: RetentionData[];
    promotions: PromotionPerformance[];
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
