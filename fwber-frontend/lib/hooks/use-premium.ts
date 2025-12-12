import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface PremiumStatus {
  is_premium: boolean;
  tier: 'free' | 'gold';
  expires_at: string | null;
  unlimited_swipes: boolean;
}

export const usePremiumStatus = () => {
  return useQuery<PremiumStatus>({
    queryKey: ['premium-status'],
    queryFn: async () => {
      return await api.get<PremiumStatus>('/premium/status');
    },
  });
};

export const usePurchasePremium = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (data?: { paymentMethod?: 'stripe' | 'token' }) => {
      return await api.post('/premium/purchase', { payment_method: data?.paymentMethod });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['premium-status'] });
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
};

export const useInitiatePurchase = () => {
  return useMutation({
    mutationFn: async (planId: string = 'gold_monthly') => {
      return await api.post<{ client_secret: string }>('/premium/initiate', { plan_id: planId });
    },
  });
};

export const useWhoLikesYou = () => {
  return useQuery({
    queryKey: ['who-likes-you'],
    queryFn: async () => {
      return await api.get<any[]>('/premium/who-likes-you');
    },
    retry: false,
  });
};
