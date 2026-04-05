import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';
import { useAuth } from '@/lib/auth-context';

export interface Boost {
  id: number;
  user_id: number;
  started_at: string;
  expires_at: string;
  boost_type: 'standard' | 'super';
  status: 'active' | 'expired';
}

interface ActiveBoostResponse {
  data: Boost | null;
  message?: string;
}

export function useActiveBoost() {
  const { token, isAuthenticated } = useAuth();

  return useQuery({
    queryKey: ['active-boost'],
    enabled: isAuthenticated && !!token,
    queryFn: async () => {
      const response = await api.get<ActiveBoostResponse>('/boosts/active');
      return response.data;
    },
    refetchInterval: (query) => (query.state.data ? 60000 : false),
  });
}

export function useBoostHistory() {
  const { token, isAuthenticated } = useAuth();

  return useQuery({
    queryKey: ['boost-history'],
    enabled: isAuthenticated && !!token,
    queryFn: () => api.get<Boost[]>('/boosts/history'),
  });
}

export function usePurchaseBoost() {
  const queryClient = useQueryClient();
  const { token, isAuthenticated } = useAuth();

  return useMutation({
    mutationFn: async ({ type, paymentMethod }: { type: 'standard' | 'super'; paymentMethod?: 'stripe' | 'token' }) => {
      if (!isAuthenticated || !token) {
        throw new Error('Authentication required');
      }

      return api.post<Boost>('/boosts/purchase', { type, payment_method: paymentMethod });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['active-boost'] });
      queryClient.invalidateQueries({ queryKey: ['boost-history'] });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
    },
  });
}
