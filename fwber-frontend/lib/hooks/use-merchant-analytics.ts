import { useQuery } from '@tanstack/react-query';
import axios from '@/lib/api/axios';
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
            const { data } = await axios.get('/merchant-portal/analytics', { params: { range } });
            return data;
        },
        // Keep generic placeholder data until backend is fully seeded in production
        placeholderData: (previousData) => previousData,
    });
}
