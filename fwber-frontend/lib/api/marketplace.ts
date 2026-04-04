import { api } from './client'

export interface MerchantSummary {
  id: number
  business_name: string
  description?: string | null
  category?: string
  address?: string | null
  location_name?: string | null
  latitude?: number | null
  longitude?: number | null
  verification_status?: string
  trust_score?: number
  trust_tier?: string
  trust_breakdown?: Record<string, number>
}

export interface InventoryItem {
  id: number
  merchant_profile_id: number
  name: string
  description: string | null
  price_usd: string | number
  stock_count: number
  image_url?: string | null
  is_available: boolean
  distance_m?: number | null
  lat?: number | null
  lng?: number | null
  merchant?: MerchantSummary
}

export interface PurchaseResponse {
  message: string
  merchant_name?: string
  item_name?: string
  redemption_code: string
  remaining_stock?: number
  receipt?: {
    id: number
    amount: string | number
    currency: string
    status: string
    paid_at: string
  }
}

export const marketplaceApi = {
  getNearby: (params?: { lat?: number; lng?: number; radius?: number; limit?: number }) => api.get<{ items: (InventoryItem & { merchant?: MerchantSummary })[] }>('/marketplace/nearby', { params }),

  getInventory: (merchantId: string | number) =>
    api.get<{ merchant: MerchantSummary; items: InventoryItem[] }>(`/marketplace/${merchantId}`),

  purchaseItem: (itemId: number, payload?: { payment_method_id?: string; payment_intent_id?: string }) =>
    api.post<PurchaseResponse>(`/marketplace/purchase/${itemId}`, payload ?? {}),

  getOwnedInventory: () =>
    api.get<{ items: InventoryItem[] }>('/merchant-portal/inventory'),

  createItem: (data: Partial<InventoryItem> & { price_usd: number; stock_count: number; name: string }) =>
    api.post<{ item: InventoryItem; message: string }>('/merchant-portal/inventory', data),

  updateItem: (id: number, data: Partial<InventoryItem>) =>
    api.put<{ item: InventoryItem; message: string }>(`/merchant-portal/inventory/${id}`, data),

  archiveItem: (id: number) =>
    api.delete<{ item: InventoryItem; message: string }>(`/merchant-portal/inventory/${id}`),

  redeemCode: (code: string) =>
    api.post<{ success: boolean; item_name: string; user_name: string; redeemed_at: string }>('/merchant-portal/inventory/redeem', { code }),
}
