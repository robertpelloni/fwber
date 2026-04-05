import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface MatchInsightsUnlockedData {
  total_score: number;
  is_locked: false;
  breakdown: {
    base: number;
    preferences: number;
    behavioral: number;
    communication: number;
    mutual: number;
    scene?: number;
    recency: number;
  };
  details: {
    physical: number;
    sexual: number;
    lifestyle: number;
    personality: number;
  };
}

export interface MatchInsightsLockedData {
  total_score: number;
  is_locked: true;
  cost: number;
  preview_message: string;
}

export type MatchInsightsData = MatchInsightsUnlockedData | MatchInsightsLockedData;

interface MatchInsightsResponse {
  success: boolean;
  data: MatchInsightsData;
}

export function useMatchInsights(matchId: string) {
  return useQuery({
    queryKey: ['match-insights', matchId],
    queryFn: async () => {
      const response = await api.get<MatchInsightsResponse>(`/matches/${matchId}/insights`);
      return response.data;
    },
    enabled: !!matchId,
  });
}

export function useUnlockMatchInsights(matchId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => api.post<{ message: string; balance: number }>(`/matches/${matchId}/insights/unlock`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['match-insights', matchId] });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
    },
  });
}
