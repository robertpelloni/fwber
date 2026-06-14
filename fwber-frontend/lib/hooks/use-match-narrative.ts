import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export function useMatchNarrative(matchId: string) {
  return useQuery({
    queryKey: ['match-narrative', matchId],
    queryFn: async () => {
      // Backend route is GET /api/matching/narrative/:matchId
      // The frontend 'api' client might already prefix with /api or similar depending on its config
      const response = await api.get<{ report: string }>(`/matching/narrative/${matchId}`);
      return response.report;
    },
    enabled: !!matchId,
    staleTime: 1000 * 60 * 30, // 30 minutes
  });
}
