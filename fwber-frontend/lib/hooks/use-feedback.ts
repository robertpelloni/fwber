import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface FeedbackItem {
  id: number;
  user_id: number | null;
  category: 'bug' | 'feature' | 'general' | 'safety';
  message: string;
  page_url: string | null;
  metadata: any;
  status: 'new' | 'reviewed' | 'resolved' | 'dismissed';
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

interface FeedbackResponse {
  data: FeedbackItem[];
  current_page: number;
  last_page: number;
  total: number;
}

export function useFeedback(page = 1) {
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: ['feedback', page],
    queryFn: async () => {
      return api.get<FeedbackResponse>(`/feedback?page=${page}`);
    },
  });

  const updateStatus = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      return api.put(`/feedback/${id}`, { status });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['feedback'] });
    },
  });

  return {
    ...query,
    updateStatus,
  };
}
