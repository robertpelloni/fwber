import { apiClient } from './client';
import { PaginatedResponse } from './types';

export interface Event {
  id: number;
  title: string;
  description: string;
  type?: 'standard' | 'speed_dating' | 'party' | 'meetup' | 'workshop';
  location_name: string;
  latitude: number;
  longitude: number;
  starts_at: string;
  ends_at: string;
  max_attendees: number | null;
  price: number | null;
  token_cost?: number | null;
  created_by_user_id: number;
  status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled';
  attendees_count: number;
  creator?: any;
  attendees?: any[];
}

export interface CreateEventRequest {
  title: string;
  description: string;
  type?: 'standard' | 'speed_dating' | 'party' | 'meetup' | 'workshop';
  location_name: string;
  latitude: number;
  longitude: number;
  starts_at: string;
  ends_at: string;
  max_attendees?: number;
  price?: number;
  token_cost?: number;
}

export async function getNearbyEvents(latitude?: number, longitude?: number, radius: number = 50, type?: string): Promise<PaginatedResponse<Event>> {
  const params: any = { radius };
  if (latitude && longitude) {
    params.latitude = latitude;
    params.longitude = longitude;
  }
  if (type) {
    params.type = type;
  }
  const response = await apiClient.get<PaginatedResponse<Event>>('/events', { params });
  return response.data;
}

export async function getMyEvents(): Promise<PaginatedResponse<Event>> {
  const response = await apiClient.get<PaginatedResponse<Event>>('/events/my-events');
  return response.data;
}

export async function getEvent(id: string | number): Promise<Event> {
  const response = await apiClient.get<Event>(`/events/${id}`);
  return response.data;
}

export async function createEvent(data: CreateEventRequest): Promise<Event> {
  const response = await apiClient.post<Event>('/events', data);
  return response.data;
}

export async function rsvpEvent(
  id: number, 
  status: string, 
  paymentMethod?: 'stripe' | 'token',
  paymentMethodId?: string
): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/events/${id}/rsvp`, { 
    status,
    payment_method: paymentMethod,
    payment_method_id: paymentMethodId
  });
  return response.data;
}
