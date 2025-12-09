/**
 * React Query hooks for configuration and feature flags
 */

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  getFeatureFlags,
  getSystemHealth,
  updateFeatureFlags,
  type FeatureFlags,
  type FeatureFlagsResponse,
  type SystemHealth,
} from '@/lib/api/config';

interface ConfigQueryOptions {
  enabled?: boolean;
  refetchInterval?: number;
  staleTime?: number;
}

const DEFAULT_STALE_TIME = 60_000; // 1 minute
const DEFAULT_GC_TIME = 5 * 60_000; // 5 minutes

/**
 * Hook to fetch backend feature flags
 */
export function useBackendFeatureFlags(options: ConfigQueryOptions = {}) {
  return useQuery<FeatureFlagsResponse>({
    queryKey: ['config', 'features'],
    queryFn: getFeatureFlags,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval,
    staleTime: options.staleTime ?? DEFAULT_STALE_TIME,
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
    retry: 2,
  });
}

/**
 * Hook to update feature flags
 */
export function useUpdateFeatureFlags() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (features: Partial<FeatureFlags>) => updateFeatureFlags(features),
    onSuccess: () => {
      // Invalidate feature flags query to refetch
      queryClient.invalidateQueries({ queryKey: ['config', 'features'] });
    },
  });
}

/**
 * Hook to fetch system health including Mercure status
 */
export function useSystemHealth(options: ConfigQueryOptions = {}) {
  return useQuery<SystemHealth>({
    queryKey: ['config', 'health'],
    queryFn: getSystemHealth,
    enabled: options.enabled ?? true,
    refetchInterval: options.refetchInterval ?? 30_000, // Every 30 seconds
    staleTime: options.staleTime ?? 15_000, // 15 seconds
    gcTime: DEFAULT_GC_TIME,
    refetchOnWindowFocus: false,
    retry: 1,
  });
}
