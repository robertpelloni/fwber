import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { moderationApi } from '../api/moderation';

export function useModerationDashboard(token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'dashboard'],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return moderationApi.dashboard(token);
    },
    enabled: !!token,
    staleTime: 30000,
    refetchInterval: 60000,
  });
}

export function useFlaggedContent(page: number, token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'flagged', page],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return moderationApi.flaggedContent(token, page);
    },
    enabled: !!token,
  });
}

export function useSpoofDetections(page: number, token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'spoofs', page],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return moderationApi.spoofDetections(token, page);
    },
    enabled: !!token,
  });
}

export function useThrottles(page: number, token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'throttles', page],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return moderationApi.throttles(token, page);
    },
    enabled: !!token,
  });
}

export function useActions(page: number, token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'actions', page],
    queryFn: () => {
      if (!token) throw new Error('Authentication required');
      return moderationApi.actions(token, page);
    },
    enabled: !!token,
  });
}

export function useUserModerationProfile(userId: number | null, token: string | null) {
  return useQuery({
    queryKey: ['moderation', 'user', userId],
    queryFn: () => {
      if (!token || !userId) throw new Error('Authentication required');
      return moderationApi.userProfile(userId, token);
    },
    enabled: !!token && !!userId,
  });
}

export function useReviewFlagMutation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ artifactId, payload, token }: { artifactId: number; payload: any; token: string }) =>
      moderationApi.reviewFlag(artifactId, payload, token),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['moderation'] });
      qc.invalidateQueries({ queryKey: ['local-pulse'] });
      qc.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    },
  });
}

export function useReviewSpoofMutation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ detectionId, payload, token }: { detectionId: number; payload: any; token: string }) =>
      moderationApi.reviewSpoof(detectionId, payload, token),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['moderation'] });
    },
  });
}

export function useRemoveThrottleMutation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ throttleId, token }: { throttleId: number; token: string }) =>
      moderationApi.removeThrottle(throttleId, token),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['moderation'] });
    },
  });
}
