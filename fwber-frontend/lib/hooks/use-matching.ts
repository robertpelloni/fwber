import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../api/client';

export interface MatchingQuestion {
  id: string;
  text: string;
  category: string;
  options: { id: string; text: string }[];
  answer: {
    chosen_option_id: string;
    accepted_option_ids: string[];
    importance: number;
    explanation?: string;
  } | null;
}

export function useMatchingQuestions() {
  return useQuery({
    queryKey: ['matching-questions'],
    queryFn: async () => {
      const response: any = await api.get<MatchingQuestion[]>('/api/matching/questions');
      return (response as any).data;
    },
  });
}

export function useSubmitMatchingAnswer() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: {
      question_id: string;
      chosen_option_id: string;
      accepted_option_ids: string[];
      importance: number;
      explanation?: string;
    }) => {
      const response: any = await api.post('/api/matching/answer', data);
      return (response as any).data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['matching-questions'] });
    },
  });
}
