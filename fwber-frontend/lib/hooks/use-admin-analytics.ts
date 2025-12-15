import { useQuery } from '@tanstack/react-query';
import {
  getBoostAnalytics,
  getModerationInsights,
  getPlatformAnalytics,
  getRateLimitStatistics,
  getRealtimeAnalytics,
  getRetentionAnalytics,
  getSlowRequests,
  getSlowRequestStats,
  getSlowRequestAnalysis,
} from '@/lib/api/admin';
import type {
  AnalyticsRange,
  BoostAnalyticsResponse,
  ModerationInsights,
  PlatformAnalyticsResponse,
  RateLimitStatsResponse,
  RateLimitTimeframe,
  RealtimeMetrics,
  RetentionResponse,
  SlowRequest,
  SlowRequestStats,
  SlowRequestAnalysis,
} from '@/lib/api/types';

interface AdminQueryOptions {
  enabled?: boolean;
  refetchInterval?: number;
  staleTime?: number;
}

const DEFAULT_STALE_TIME = 60_000; // 1 minute
const DEFAULT_GC_TIME = 5 * 60_000; // 5 minutes

export function usePlatformAnalytics(range: AnalyticsRange, options: AdminQueryOptions = {}) {
  return useQuery<PlatformAnalyticsResponse>({
    queryKey: ['platform-analytics', range],
    queryFn: () => getPlatformAnalytics(range),
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval,
    staleTime: options.staleTime ?? DEFAULT_STALE_TIME,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useRealtimeMetrics(options: AdminQueryOptions = {}) {
  return useQuery<RealtimeMetrics>({
    queryKey: ['analytics', 'realtime'],
    queryFn: getRealtimeAnalytics,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 15_000,
    staleTime: options.staleTime ?? 10_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useModerationInsights(options: AdminQueryOptions = {}) {
  return useQuery<ModerationInsights>({
    queryKey: ['analytics', 'moderation'],
    queryFn: getModerationInsights,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 5 * 60_000,
    staleTime: options.staleTime ?? 5 * 60_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useBoostAnalytics(options: AdminQueryOptions = {}) {
  return useQuery<BoostAnalyticsResponse>({
    queryKey: ['analytics', 'boosts'],
    queryFn: getBoostAnalytics,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 60_000,
    staleTime: options.staleTime ?? 30_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useRetentionAnalytics(options: AdminQueryOptions = {}) {
  return useQuery<RetentionResponse>({
    queryKey: ['analytics', 'retention'],
    queryFn: getRetentionAnalytics,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 3600_000, // 1 hour
    staleTime: options.staleTime ?? 3600_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useSlowRequests(options: AdminQueryOptions = {}) {
  return useQuery<SlowRequest[]>({
    queryKey: ['analytics', 'slow-requests'],
    queryFn: getSlowRequests,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 60_000,
    staleTime: options.staleTime ?? 30_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useSlowRequestStats(options: AdminQueryOptions = {}) {
  return useQuery<SlowRequestStats[]>({
    queryKey: ['analytics', 'slow-requests', 'stats'],
    queryFn: getSlowRequestStats,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 60_000,
    staleTime: options.staleTime ?? 30_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useSlowRequestAnalysis(options: AdminQueryOptions = {}) {
  return useQuery<SlowRequestAnalysis[]>({
    queryKey: ['analytics', 'slow-requests', 'analysis'],
    queryFn: getSlowRequestAnalysis,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 60_000,
    staleTime: options.staleTime ?? 30_000,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}

export function useRateLimitStatistics(
  timeframe: RateLimitTimeframe,
  options: AdminQueryOptions = {}
) {
  return useQuery<RateLimitStatsResponse>({
    queryKey: ['rate-limits', 'stats', timeframe],
    queryFn: () => getRateLimitStatistics(timeframe),
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval,
    staleTime: options.staleTime ?? DEFAULT_STALE_TIME,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
  });
}
