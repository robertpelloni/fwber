import { api } from './client';
import type {
  AnalyticsRange,
  PlatformAnalyticsResponse,
  RealtimeMetrics,
  ModerationInsights,
  RateLimitStatsResponse,
  RateLimitTimeframe,
  SlowRequest,
} from './types';

const DEFAULT_ANALYTICS_RANGE: AnalyticsRange = '7d';
const DEFAULT_RATE_LIMIT_TIMEFRAME: RateLimitTimeframe = '1h';

export function getPlatformAnalytics(range: AnalyticsRange = DEFAULT_ANALYTICS_RANGE) {
  return api.get<PlatformAnalyticsResponse>('/analytics', {
    params: { range },
  });
}

export function getRealtimeAnalytics() {
  return api.get<RealtimeMetrics>('/analytics/realtime');
}

export function getModerationInsights() {
  return api.get<ModerationInsights>('/analytics/moderation');
}

export function getSlowRequests() {
  return api.get<SlowRequest[]>('/analytics/slow-requests');
}

export function getRateLimitStatistics(timeframe: RateLimitTimeframe = DEFAULT_RATE_LIMIT_TIMEFRAME) {
  return api.get<RateLimitStatsResponse>(`/rate-limits/stats/${timeframe}`);
}
