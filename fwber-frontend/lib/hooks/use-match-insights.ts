import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface MatchInsightsData {
  total_score: number;
  breakdown: {
    base: number;
    preferences: number;
    behavioral: number;
    communication: number;
    mutual: number;
    recency: number;
  };
  details: {
    physical: number;
    sexual: number;
    lifestyle: number;
    personality: number;
  };
}

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
