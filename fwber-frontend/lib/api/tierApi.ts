import { enhancedFetch } from './apiErrorHandling'

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

export interface TierResponse {
  match_id: number
  current_tier: string
  messages_exchanged: number
  days_connected: number
  has_met_in_person: boolean
  tier_info: {
    name: string
    icon: string
    color: string
    unlocks: string[]
  }
  created_at: string
  updated_at: string
}

export interface TierUpdateResponse extends TierResponse {
  previous_tier: string
  tier_upgraded: boolean
}

export interface PhotoResponse {
  match_id: number
  current_tier: string
  ai_photos: Array<{
    id: number
    url: string
    thumbnail_url: string | null
    type: 'ai'
    is_primary: boolean
  }>
  real_photos: {
    visible: Array<{
      id: number
      url: string
      thumbnail_url: string | null
      type: 'real'
      is_primary: boolean
      blurred: false
    }>
    blurred: Array<{
      id: number
      url: string
      thumbnail_url: string | null
      type: 'real'
      is_primary: boolean
      blurred: true
    }>
    locked: number
  }
  unlock_requirements: {
    next_tier: string | null
    requirements: Array<{
      description: string
      met: boolean
    }>
  }
}

/**
 * Get authentication token from localStorage
 */
function getAuthToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem('auth_token')
}

/**
 * Build authenticated request headers
 */
function getAuthHeaders(): HeadersInit {
  const token = getAuthToken()
  return {
    'Content-Type': 'application/json',
    ...(token && { 'Authorization': `Bearer ${token}` })
  }
}

/**
 * Get relationship tier progress for a match
 */
export async function getMatchTier(matchId: number): Promise<TierResponse> {
  return enhancedFetch<TierResponse>(
    `${API_BASE_URL}/matches/${matchId}/tier`,
    {
      method: 'GET',
      headers: getAuthHeaders(),
    }
  )
}

/**
 * Update relationship tier metrics
 */
export async function updateMatchTier(
  matchId: number,
  data: {
    increment_messages?: boolean
    mark_met_in_person?: boolean
  }
): Promise<TierUpdateResponse> {
  return enhancedFetch<TierUpdateResponse>(
    `${API_BASE_URL}/matches/${matchId}/tier/update`,
    {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(data),
    }
  )
}

/**
 * Get photos for a match filtered by current tier
 */
export async function getMatchPhotos(matchId: number): Promise<PhotoResponse> {
  return enhancedFetch<PhotoResponse>(
    `${API_BASE_URL}/matches/${matchId}/photos`,
    {
      method: 'GET',
      headers: getAuthHeaders(),
    }
  )
}

/**
 * Increment message count for a match (shorthand)
 */
export async function incrementMatchMessages(matchId: number): Promise<TierUpdateResponse> {
  return updateMatchTier(matchId, { increment_messages: true })
}

/**
 * Mark match as met in person (shorthand)
 */
export async function markMatchMetInPerson(matchId: number): Promise<TierUpdateResponse> {
  return updateMatchTier(matchId, { mark_met_in_person: true })
}
