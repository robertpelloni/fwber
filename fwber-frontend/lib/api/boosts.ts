import { api } from './client';

export interface Boost {
  id: number;
  user_id: number;
  started_at?: string;
  expires_at: string;
  boost_type?: 'standard' | 'super';
  status?: 'active' | 'expired';
  created_at: string;
  updated_at: string;
}

interface ActiveBoostResponse {
  data: Boost | null;
  message?: string;
}

/**
 * Purchase a profile boost
 */
export async function purchaseBoost(payload: { type: 'standard' | 'super'; payment_method?: 'stripe' | 'token'; payment_method_id?: string }): Promise<Boost> {
  return api.post<Boost>('/boosts/purchase', payload);
}

/**
 * Get active boost status
 */
export async function getActiveBoost(): Promise<Boost | null> {
  try {
    const response = await api.get<ActiveBoostResponse>('/boosts/active');
    return response.data;
  } catch (error) {
    return null;
  }
}

/**
 * Get boost history
 */
export async function getBoostHistory(): Promise<Boost[]> {
  return api.get<Boost[]>('/boosts/history');
}
