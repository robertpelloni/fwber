import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

export interface Boost {
  id: number;
  user_id: number;
  started_at: string;
  expires_at: string;
  boost_type: 'standard' | 'super';
  status: 'active' | 'expired';
}

export function useActiveBoost() {
  return useQuery({
    queryKey: ['active-boost'],
    queryFn: async () => {
      const token = localStorage.getItem('fwber_token');
      try {
        const response = await axios.get<Boost>(
          `${process.env.NEXT_PUBLIC_API_URL}/boosts/active`,
          { headers: { Authorization: `Bearer ${token}` } }
        );
        return response.data;
      } catch (error: any) {
        if (error.response?.status === 404) {
          return null;
        }
        throw error;
      }
    },
    refetchInterval: (query) => {
      // If we have an active boost, poll every minute to check expiration
      return query.state.data ? 60000 : false;
    },
  });
}

export function useBoostHistory() {
  return useQuery({
    queryKey: ['boost-history'],
    queryFn: async () => {
      const token = localStorage.getItem('fwber_token');
      const response = await axios.get<Boost[]>(
        `${process.env.NEXT_PUBLIC_API_URL}/boosts/history`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    },
  });
}

export function usePurchaseBoost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ type, paymentMethod }: { type: 'standard' | 'super', paymentMethod?: 'stripe' | 'token' }) => {
      const token = localStorage.getItem('fwber_token');
      const response = await axios.post<Boost>(
        `${process.env.NEXT_PUBLIC_API_URL}/boosts/purchase`,
        { type, payment_method: paymentMethod },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['active-boost'] });
      queryClient.invalidateQueries({ queryKey: ['boost-history'] });
    },
  });
}
