import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useSession } from 'next-auth/react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

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
  const { data: session } = useSession();

  return useQuery({
    queryKey: ['events', 'nearby', latitude, longitude, radius],
    queryFn: async () => {
      const params: any = { radius };
      if (latitude && longitude) {
        params.latitude = latitude;
        params.longitude = longitude;
      }
      const { data } = await axios.get(`${API_URL}/events`, {
        params,
        headers: { Authorization: `Bearer ${session?.accessToken}` },
      });
      return data;
    },
    enabled: !!session?.accessToken,
  });
}

export function useMyEvents() {
  const { data: session } = useSession();

  return useQuery({
    queryKey: ['events', 'my'],
    queryFn: async () => {
      const { data } = await axios.get(`${API_URL}/events/my-events`, {
        headers: { Authorization: `Bearer ${session?.accessToken}` },
      });
      return data;
    },
    enabled: !!session?.accessToken,
  });
}

export function useEvent(id: string) {
  const { data: session } = useSession();

  return useQuery({
    queryKey: ['events', id],
    queryFn: async () => {
      const { data } = await axios.get(`${API_URL}/events/${id}`, {
        headers: { Authorization: `Bearer ${session?.accessToken}` },
      });
      return data;
    },
    enabled: !!session?.accessToken && !!id,
  });
}

export function useCreateEvent() {
  const queryClient = useQueryClient();
  const { data: session } = useSession();

  return useMutation({
    mutationFn: async (newEvent: any) => {
      const { data } = await axios.post(`${API_URL}/events`, newEvent, {
        headers: { Authorization: `Bearer ${session?.accessToken}` },
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
    },
  });
}

export function useRsvpEvent() {
  const queryClient = useQueryClient();
  const { data: session } = useSession();

  return useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      const { data } = await axios.post(`${API_URL}/events/${id}/rsvp`, { status }, {
        headers: { Authorization: `Bearer ${session?.accessToken}` },
      });
      return data;
    },
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['events', id.toString()] });
      queryClient.invalidateQueries({ queryKey: ['events', 'my'] });
    },
  });
}
