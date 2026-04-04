import { api } from './client'

export type MerchantVerificationStatus = 'pending' | 'verified' | 'rejected'

export interface MerchantProfile {
  id: number
  user_id: number
  business_name: string
  category: string
  description: string | null
  address: string | null
  location_name: string | null
  latitude: number | null
  longitude: number | null
  verification_status: MerchantVerificationStatus
  created_at: string
  updated_at: string
}

export interface MerchantInventoryItem {
  id: number
  merchant_profile_id: number
  name: string
  description: string | null
  price_usd: string | number
  stock_count: number
  image_url?: string | null
  is_available: boolean
  pending_redemptions_count?: number
  created_at?: string
  updated_at?: string
}

export interface MerchantDashboardResponse {
  profile: MerchantProfile
  stats: {
    inventory_count: number
    active_items: number
    total_stock: number
    pending_redemptions: number
    redeemed_count: number
    gross_revenue: number
  }
  recent_inventory: MerchantInventoryItem[]
  recent_redemptions: Array<{
    id: number
    redemption_code: string
    redeemed_at: string | null
    created_at: string
    inventory?: { name?: string }
    user?: { name?: string }
  }>
  storefront_path: string
}

export interface MerchantAnalyticsResponse {
  summary: {
    gross_revenue: number
    orders: number
    issued_redemptions: number
    redeemed_redemptions: number
    redemption_rate: number
    average_order_value: number
  }
  top_items: MerchantInventoryItem[]
  recent_payments: Array<{
    id: number
    amount: string
    status: string
    created_at: string
    description?: string | null
  }>
  recent_redemptions: Array<{
    id: number
    redemption_code: string
    redeemed_at: string | null
    created_at: string
    inventory?: { name?: string }
    user?: { name?: string }
  }>
}

export interface MerchantRegistrationData {
  business_name: string
  category: string
  description?: string
  address?: string
  location_name?: string
  latitude?: number | null
  longitude?: number | null
}

export interface CreateInventoryData {
  name: string
  description?: string
  price_usd: number
  stock_count: number
  image_url?: string
  is_available?: boolean
}

export async function registerMerchant(data: MerchantRegistrationData) {
  return api.post<{ message: string; profile: MerchantProfile; user: any }>('/merchant-portal/register', data)
}

export async function getMerchantProfile() {
  return api.get<{ profile: MerchantProfile }>('/merchant-portal/profile')
}

export async function updateMerchantProfile(data: Partial<MerchantRegistrationData>) {
  return api.put<{ message: string; profile: MerchantProfile }>('/merchant-portal/profile', data)
}

export async function getMerchantDashboard() {
  return api.get<MerchantDashboardResponse>('/merchant-portal/dashboard')
}

export async function getMerchantAnalytics(range: '7d' | '30d' | '90d' = '30d') {
  return api.get<MerchantAnalyticsResponse>('/merchant-portal/analytics', { params: { range } })
}

export async function getInventory() {
  return api.get<{ items: MerchantInventoryItem[] }>('/merchant-portal/inventory')
}

export async function createInventoryItem(data: CreateInventoryData) {
  return api.post<{ message: string; item: MerchantInventoryItem }>('/merchant-portal/inventory', data)
}

export async function updateInventoryItem(id: number, data: Partial<CreateInventoryData>) {
  return api.put<{ message: string; item: MerchantInventoryItem }>(`/merchant-portal/inventory/${id}`, data)
}

export async function archiveInventoryItem(id: number) {
  return api.delete<{ message: string; item: MerchantInventoryItem }>(`/merchant-portal/inventory/${id}`)
}

export async function redeemMerchantCode(code: string) {
  return api.post<{ success: boolean; item_name: string; user_name: string; redeemed_at: string }>('/merchant-portal/inventory/redeem', { code })
}
