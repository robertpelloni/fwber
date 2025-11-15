import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { proximityApi } from '../api/proximity';
import type {
  LocalPulseParams,
  LocalPulseResponse,
  CreateArtifactRequest,
} from '@/types/proximity';

/**
 * Hook to fetch Local Pulse merged feed (artifacts + match candidates)
 */
export function useLocalPulse(params: LocalPulseParams, token: string | null, enabled: boolean = true) {
  return useQuery<LocalPulseResponse>({
    queryKey: ['local-pulse', params.lat, params.lng, params.radius],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return proximityApi.getLocalPulse(params, token);
    },
    enabled: enabled && !!token && !!params.lat && !!params.lng,
    staleTime: 30000, // 30 seconds
    refetchInterval: 60000, // Refetch every minute
  });
}

/**
 * Hook to fetch proximity artifacts feed only
 */
export function useProximityArtifacts(
  lat: number,
  lng: number,
  radius: number = 1000,
  type?: string,
  token?: string | null,
  enabled: boolean = true
) {
  return useQuery({
    queryKey: ['proximity-artifacts', lat, lng, radius, type],
    queryFn: () => proximityApi.getArtifactsFeed(lat, lng, radius, type, token || undefined),
    enabled: enabled && !!lat && !!lng,
    staleTime: 30000,
    refetchInterval: 60000,
  });
}

/**
 * Hook to create a proximity artifact
 */
export function useCreateProximityArtifact() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ data, token }: { data: CreateArtifactRequest; token: string }) =>
      proximityApi.createArtifact(data, token),
    onSuccess: () => {
      // Invalidate and refetch local pulse and artifacts queries
      queryClient.invalidateQueries({ queryKey: ['local-pulse'] });
      queryClient.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    },
  });
}

/**
 * Hook to flag an artifact
 */
export function useFlagProximityArtifact() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, token }: { id: number; token: string }) =>
      proximityApi.flagArtifact(id, token),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['local-pulse'] });
      queryClient.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    },
  });
}

/**
 * Hook to delete an artifact
 */
export function useDeleteProximityArtifact() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, token }: { id: number; token: string }) =>
      proximityApi.deleteArtifact(id, token),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['local-pulse'] });
      queryClient.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    },
  });
}
