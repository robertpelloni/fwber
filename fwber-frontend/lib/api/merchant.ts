
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export type MerchantVerificationStatus = 'pending' | 'verified' | 'rejected';

export interface MerchantProfile {
  id: number;
  user_id: number;
  business_name: string;
  category: string;
  description: string | null;
  address: string | null;
  verification_status: MerchantVerificationStatus;
  created_at: string;
  updated_at: string;
}

export interface Promotion {
  id: number;
  merchant_id: number;
  title: string;
  description: string | null;
  promo_code?: string | null;
  discount_value: string;
  lat: number;
  lng: number;
  radius: number;
  token_cost: number;
  starts_at: string;
  expires_at: string;
  is_active: boolean;
  views?: number;
  clicks?: number;
  redemptions?: number;
  created_at: string;
  updated_at: string;
}

export interface PromotionMetrics {
  views: number;
  clicks: number;
  redemptions: number;
  conversion_rate: number;
}

export interface PromotionDetailResponse {
  promotion: Promotion;
  metrics: PromotionMetrics;
}

export interface MerchantRegistrationData {
  business_name: string;
  category: string;
  description?: string;
  address?: string;
}

export interface CreatePromotionData {
  title: string;
  description?: string;
  promo_code?: string;
  discount_value: string;
  lat: number;
  lng: number;
  radius: number;
  starts_at: string;
  expires_at: string;
}

export interface UpdatePromotionData {
  title?: string;
  description?: string | null;
  promo_code?: string | null;
  discount_value?: string;
  radius?: number;
  token_cost?: number;
  starts_at?: string;
  expires_at?: string;
  is_active?: boolean;
}

/**
 * Register the current user as a merchant
 */
export async function registerMerchant(token: string, data: MerchantRegistrationData): Promise<MerchantProfile> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/register`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to register merchant' }));
    throw new Error(error.message || 'Failed to register merchant');
  }

  const result = await response.json();
  return result.user?.merchant_profile || result.user?.merchantProfile || result.profile || result.data || result;
}

/**
 * Get current merchant profile
 */
export async function getMerchantProfile(token: string): Promise<MerchantProfile> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/profile`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch merchant profile' }));
    throw new Error(error.message || 'Failed to fetch merchant profile');
  }

  const result = await response.json();
  return result.profile || result.data || result;
}

/**
 * Update merchant profile
 */
export async function updateMerchantProfile(token: string, data: Partial<MerchantRegistrationData>): Promise<MerchantProfile> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/profile`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update merchant profile' }));
    throw new Error(error.message || 'Failed to update merchant profile');
  }

  const result = await response.json();
  return result.profile || result.data || result;
}

/**
 * Get active promotions
 */
export async function getPromotions(token: string): Promise<Promotion[]> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/promotions`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch promotions' }));
    throw new Error(error.message || 'Failed to fetch promotions');
  }

  const result = await response.json();
  return result.data || result; // Assuming Laravel Resource returns { data: [...] }
}

/**
 * Create a new promotion
 */
export async function createPromotion(token: string, data: CreatePromotionData): Promise<Promotion> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/promotions`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to create promotion' }));
    throw new Error(error.message || 'Failed to create promotion');
  }

  const result = await response.json();
  return result.promotion || result.data || result;
}

export async function getPromotionDetail(token: string, promotionId: number | string): Promise<PromotionDetailResponse> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/promotions/${promotionId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch promotion details' }));
    throw new Error(error.message || 'Failed to fetch promotion details');
  }

  const result = await response.json();

  return {
    promotion: result.promotion || result.data || result,
    metrics: result.metrics || {
      views: result.promotion?.views ?? 0,
      clicks: result.promotion?.clicks ?? 0,
      redemptions: result.promotion?.redemptions ?? 0,
      conversion_rate: 0,
    },
  };
}

export async function updatePromotion(token: string, promotionId: number | string, data: UpdatePromotionData): Promise<Promotion> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/promotions/${promotionId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update promotion' }));
    throw new Error(error.message || 'Failed to update promotion');
  }

  const result = await response.json();
  return result.promotion || result.data || result;
}

export async function deletePromotion(token: string, promotionId: number | string): Promise<Promotion> {
  const response = await fetch(`${API_BASE_URL}/merchant-portal/promotions/${promotionId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to deactivate promotion' }));
    throw new Error(error.message || 'Failed to deactivate promotion');
  }

  const result = await response.json();
  return result.promotion || result.data || result;
}
