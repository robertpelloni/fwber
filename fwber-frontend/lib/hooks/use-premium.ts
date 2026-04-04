import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export interface PremiumStatus {
  is_premium: boolean;
  tier: 'free' | 'gold';
  expires_at: string | null;
  unlimited_swipes: boolean;
  active_plan?: {
    name: string;
    status: string;
    ends_at: string | null;
  } | null;
}

export interface PremiumPlan {
  id: string;
  name: string;
  display_name: string;
  description: string;
  price_usd: number;
  currency: string;
  duration_days: number;
  interval: 'month' | 'year';
}

export interface PremiumHistoryEntry {
  id: number;
  amount?: string;
  currency?: string;
  payment_gateway?: string;
  transaction_id?: string | null;
  status: string;
  description?: string | null;
  stripe_price?: string | null;
  ends_at?: string | null;
  created_at: string;
  name?: string;
}

export interface PremiumInitiationResponse {
  client_secret: string;
  amount: number;
  currency: string;
  plan_id: string;
  plan: {
    id: string;
    name: string;
    display_name: string;
    price_usd: number;
    currency: string;
    interval: 'month' | 'year';
  };
}

export const usePremiumStatus = () => {
  return useQuery<PremiumStatus>({
    queryKey: ['premium-status'],
    queryFn: async () => {
      return await api.get<PremiumStatus>('/premium/status');
    },
  });
};

export const usePremiumPlans = () => {
  return useQuery<{ plans: PremiumPlan[] }>({
    queryKey: ['premium-plans'],
    queryFn: async () => {
      return await api.get<{ plans: PremiumPlan[] }>('/premium/plans');
    },
  });
};

export const usePremiumHistory = () => {
  return useQuery<{ payments: PremiumHistoryEntry[]; subscriptions: PremiumHistoryEntry[] }>({
    queryKey: ['premium-history'],
    queryFn: async () => {
      return await api.get<{ payments: PremiumHistoryEntry[]; subscriptions: PremiumHistoryEntry[] }>('/premium/history');
    },
  });
};

export const usePurchasePremium = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (data?: { paymentMethod?: 'stripe' | 'token'; planId?: string; paymentIntentId?: string; paymentMethodId?: string }) => {
      return await api.post('/premium/purchase', {
        payment_method: data?.paymentMethod,
        plan_id: data?.planId,
        payment_intent_id: data?.paymentIntentId,
        payment_method_id: data?.paymentMethodId,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['premium-status'] });
      queryClient.invalidateQueries({ queryKey: ['premium-history'] });
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
};

export const useInitiatePurchase = () => {
  return useMutation({
    mutationFn: async (planId: string = 'gold_monthly') => {
      return await api.post<PremiumInitiationResponse>('/premium/initiate', { plan_id: planId });
    },
  });
};

export const useWhoLikesYou = () => {
  return useQuery({
    queryKey: ['who-likes-you'],
    queryFn: async () => {
      return await api.get<any>('/premium/who-likes-you');
    },
    retry: false,
  });
};
