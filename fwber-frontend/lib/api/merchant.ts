
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface MerchantProfile {
  id: number;
  user_id: number;
  business_name: string;
  category: string;
  description: string | null;
  address: string | null;
  is_verified: boolean;
  created_at: string;
  updated_at: string;
}

export interface Promotion {
  id: number;
  merchant_id: number;
  title: string;
  description: string | null;
  discount_value: string;
  latitude: number;
  longitude: number;
  radius: number;
  token_cost: number;
  starts_at: string;
  expires_at: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
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
  discount_value: string;
  lat: number;
  lng: number;
  radius: number;
  starts_at: string;
  expires_at: string;
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
  return result.data || result;
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
  return result.data || result;
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
  return result.data || result;
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
  return result.data || result;
}
