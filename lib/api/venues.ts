import { apiClient as client } from './client'

export interface Venue {
  id: number
  name: string
  description: string
  address: string
  city: string
  state: string
  country: string
  latitude: number
  longitude: number
  features: string[]
  operating_hours: any
  is_active: boolean
}

export interface VenueCheckin {
  id: number
  user_id: number
  venue_id: number
  message: string | null
  checked_out_at: string | null
  created_at: string
  user?: {
    id: number
    name: string
    avatar?: string
  }
  venue?: Venue
}

export const getVenues = async (token: string, lat?: number, lng?: number) => {
  const params: any = {}
  if (lat !== undefined) params.lat = lat
  if (lng !== undefined) params.lng = lng
  
  const response = await client.get('/venues', {
    headers: { Authorization: `Bearer ${token}` },
    params
  })
  return response.data
}

export const getVenue = async (token: string, id: number) => {
  const response = await client.get(`/venues/${id}`, {
    headers: { Authorization: `Bearer ${token}` },
  })
  return response.data
}

export const checkInToVenue = async (
  token: string, 
  venueId: number, 
  latitude: number, 
  longitude: number, 
  message?: string
) => {
  const response = await client.post(
    `/venues/${venueId}/checkin`,
    { 
      message,
      latitude,
      longitude
    },
    { headers: { Authorization: `Bearer ${token}` } }
  )
  return response.data
}

export const checkOutFromVenue = async (token: string, venueId: number) => {
  const response = await client.post(
    `/venues/${venueId}/checkout`,
    {},
    { headers: { Authorization: `Bearer ${token}` } }
  )
  return response.data
}

export const getVenueCheckins = async (token: string, venueId: number) => {
  const response = await client.get(`/venues/${venueId}/checkins`, {
    headers: { Authorization: `Bearer ${token}` },
  })
  return response.data
}

export const getCurrentCheckin = async (token: string) => {
  const response = await client.get('/user/checkin', {
    headers: { Authorization: `Bearer ${token}` },
  })
  return response.data
}
