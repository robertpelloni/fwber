import { api } from './client';

export interface Boost {
  id: number;
  user_id: number;
  expires_at: string;
  created_at: string;
  updated_at: string;
}

export interface BoostResponse {
  success: boolean;
  message: string;
  boost: Boost;
}

/**
 * Purchase a profile boost
 */
export async function purchaseBoost(): Promise<BoostResponse> {
  return api.post<BoostResponse>('/boosts/purchase');
}

/**
 * Get active boost status
 */
export async function getActiveBoost(): Promise<Boost | null> {
  try {
    const response = await api.get<{ active: boolean; boost: Boost | null }>('/boosts/active');
    return response.boost;
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
