import { useQuery } from '@tanstack/react-query'
import { getMerchantAnalytics } from '@/lib/api/merchant'

export type AnalyticsRange = '7d' | '30d' | '90d'

export function useMerchantAnalytics(range: AnalyticsRange) {
  return useQuery({
    queryKey: ['merchant-analytics', range],
    queryFn: async () => getMerchantAnalytics(range),
    placeholderData: (previousData) => previousData,
  })
}
