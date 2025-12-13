import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getRecommendations,
  getRecommendationsByType,
  getTrendingRecommendations,
  getPersonalizedFeed,
  submitRecommendationFeedback,
  getRecommendationAnalytics,
  getCachedRecommendations,
  clearRecommendationCache,
  type RecommendationResponse,
  type TrendingResponse,
  type FeedResponse,
  type RecommendationFeedback,
  type RecommendationAnalyticsResponse,
} from '@/lib/api/recommendations';

/**
 * Hook for getting personalized recommendations
 */
export function useRecommendations(params?: {
  limit?: number;
  types?: string[];
  context?: Record<string, any>;
  enabled?: boolean;
  cacheKey?: string;
}) {
  const { cacheKey, ...queryParams } = params || {};
  
  return useQuery({
    queryKey: ['recommendations', queryParams],
    queryFn: () => {
      if (cacheKey) {
        return getCachedRecommendations(cacheKey, queryParams);
      }
      return getRecommendations(queryParams);
    },
    enabled: params?.enabled !== false,
    staleTime: 30 * 60 * 1000, // 30 minutes
    gcTime: 60 * 60 * 1000, // 1 hour
    retry: 2,
    retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
  });
}

/**
 * Hook for getting recommendations by type
 */
export function useRecommendationsByType(
  type: string,
  params?: {
    limit?: number;
    context?: Record<string, any>;
    enabled?: boolean;
  }
) {
  return useQuery({
    queryKey: ['recommendations', 'type', type, params],
    queryFn: () => getRecommendationsByType(type, params),
    enabled: params?.enabled !== false && !!type,
    staleTime: 30 * 60 * 1000,
    gcTime: 60 * 60 * 1000,
    retry: 2,
  });
}

/**
 * Hook for getting trending recommendations
 */
export function useTrendingRecommendations(params?: {
  limit?: number;
  timeframe?: '24h' | '7d' | '30d';
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['recommendations', 'trending', params],
    queryFn: () => getTrendingRecommendations(params),
    enabled: params?.enabled !== false,
    staleTime: 5 * 60 * 1000, // 5 minutes (trending changes frequently)
    gcTime: 15 * 60 * 1000, // 15 minutes
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
    retry: 2,
  });
}

/**
 * Hook for getting personalized feed
 */
export function usePersonalizedFeed(params?: {
  page?: number;
  per_page?: number;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['recommendations', 'feed', params],
    queryFn: () => getPersonalizedFeed(params),
    enabled: params?.enabled !== false,
    staleTime: 10 * 60 * 1000, // 10 minutes
    gcTime: 30 * 60 * 1000, // 30 minutes
    retry: 2,
  });
}

/**
 * Hook for submitting recommendation feedback
 */
export function useRecommendationFeedback() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (feedback: RecommendationFeedback) => 
      submitRecommendationFeedback(feedback),
    onSuccess: () => {
      // Invalidate recommendation queries to trigger refetch with updated data
      queryClient.invalidateQueries({ queryKey: ['recommendations'] });
    },
    onError: (error) => {
      console.error('Failed to submit recommendation feedback:', error);
    },
  });
}

/**
 * Hook for getting recommendation analytics (admin only)
 */
export function useRecommendationAnalytics(params?: {
  timeframe?: string;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['recommendations', 'analytics', params],
    queryFn: () => getRecommendationAnalytics(params),
    enabled: params?.enabled !== false,
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 15 * 60 * 1000, // 15 minutes
    retry: 2,
  });
}

/**
 * Hook for managing recommendation cache
 */
export function useRecommendationCache() {
  const queryClient = useQueryClient();
  
  const clearCache = () => {
    clearRecommendationCache();
    // Also clear React Query cache
    queryClient.invalidateQueries({ queryKey: ['recommendations'] });
  };
  
  const refreshRecommendations = () => {
    queryClient.invalidateQueries({ queryKey: ['recommendations'] });
  };
  
  return {
    clearCache,
    refreshRecommendations,
  };
}

/**
 * Hook for getting AI-powered recommendations specifically
 */
export function useAIRecommendations(params?: {
  limit?: number;
  context?: Record<string, any>;
  enabled?: boolean;
}) {
  return useRecommendationsByType('ai', {
    ...params,
    limit: params?.limit || 5,
  });
}

/**
 * Hook for getting location-based recommendations
 */
export function useLocationRecommendations(params?: {
  limit?: number;
  latitude?: number;
  longitude?: number;
  radius?: number;
  enabled?: boolean;
}) {
  const context = {
    latitude: params?.latitude,
    longitude: params?.longitude,
    radius: params?.radius,
  };
  
  return useRecommendationsByType('location', {
    limit: params?.limit || 5,
    context,
    enabled: params?.enabled !== false && !!(params?.latitude && params?.longitude),
  });
}

/**
 * Hook for getting collaborative recommendations
 */
export function useCollaborativeRecommendations(params?: {
  limit?: number;
  context?: Record<string, any>;
  enabled?: boolean;
}) {
  return useRecommendationsByType('collaborative', {
    ...params,
    limit: params?.limit || 5,
  });
}

/**
 * Hook for getting content-based recommendations
 */
export function useContentRecommendations(params?: {
  limit?: number;
  context?: Record<string, any>;
  enabled?: boolean;
}) {
  return useRecommendationsByType('content', {
    ...params,
    limit: params?.limit || 5,
  });
}

/**
 * Hook for getting mixed recommendations (all types combined)
 */
export function useMixedRecommendations(params?: {
  limit?: number;
  types?: string[];
  context?: Record<string, any>;
  enabled?: boolean;
}) {
  return useRecommendations({
    ...params,
    types: params?.types || ['content', 'collaborative', 'ai', 'location'],
    limit: params?.limit || 10,
  });
}

/**
 * Hook for getting recommendation performance metrics
 */
export function useRecommendationMetrics() {
  const { data: analytics } = useRecommendationAnalytics({
    timeframe: '7d',
    enabled: true,
  });
  
  return {
    analytics,
    isLoading: !analytics,
    error: null,
  };
}
