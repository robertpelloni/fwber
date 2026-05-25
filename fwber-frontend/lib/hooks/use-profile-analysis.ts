import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface ProfileAnalysisData {
  score: number;
  strengths: string[];
  weaknesses: string[];
  tips: string[];
}

export function useProfileAnalysis() {
  return useQuery<ProfileAnalysisData>({
    queryKey: ['profile-analysis'],
    queryFn: async () => {
      return await api.get<ProfileAnalysisData>('/wingman/profile-analysis');
    },
    enabled: false, // Don't fetch automatically, wait for user action
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
}
