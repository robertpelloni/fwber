import { useQuery } from '@tanstack/react-query';
import { api } from '../api/client';

export function useMatchCompatibility(matchId: string) {
  return useQuery({
    queryKey: ['match-compatibility', matchId],
    queryFn: async () => {
      const response = await api.get<{ score: number }>(`/api/matching/compatibility/${matchId}`);
      return response.data;
    },
    enabled: !!matchId,
  });
}
