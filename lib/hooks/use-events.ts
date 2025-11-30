import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api/client';
import { useAuth } from '@/lib/auth-context';
import { PaginatedResponse } from '@/lib/api/types';

export interface Event {
  id: number;
  title: string;
  description: string;
  location_name: string;
  latitude: number;
  longitude: number;
  starts_at: string;
  ends_at: string;
  max_attendees: number | null;
  price: number | null;
  created_by_user_id: number;
  status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled';
  attendees_count: number;
  creator?: any;
  attendees?: any[];
}

export function useNearbyEvents(latitude?: number, longitude?: number, radius: number = 50) {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', 'nearby', latitude, longitude, radius],
    queryFn: async () => {
      const params: any = { radius };
      if (latitude && longitude) {
        params.latitude = latitude;
        params.longitude = longitude;
      }
      const { data } = await apiClient.get<PaginatedResponse<Event>>('/events', {
        params,
      });
      return data;
    },
    enabled: !!token,
  });
}

export function useMyEvents() {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', 'my'],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<Event>>('/events/my-events');
      return data;
    },
    enabled: !!token,
  });
}

export function useEvent(id: string) {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', id],
    queryFn: async () => {
      const { data } = await apiClient.get<Event>(`/events/${id}`);
      return data;
    },
    enabled: !!token && !!id,
  });
}

export function useCreateEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (newEvent: any) => {
      const { data } = await apiClient.post('/events', newEvent);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
    },
  });
}

export function useRsvpEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      const { data } = await apiClient.post(`/events/${id}/rsvp`, { status });
      return data;
    },
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['events', id.toString()] });
      queryClient.invalidateQueries({ queryKey: ['events', 'my'] });
    },
  });
}
