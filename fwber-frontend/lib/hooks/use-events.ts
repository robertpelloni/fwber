import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuth } from '@/lib/auth-context';
import { 
  getNearbyEvents, 
  getMyEvents, 
  getEvent, 
  createEvent, 
  rsvpEvent, 
  Event 
} from '@/lib/api/events';

export type { Event };

export function useNearbyEvents(latitude?: number, longitude?: number, radius: number = 50) {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', 'nearby', latitude, longitude, radius],
    queryFn: () => getNearbyEvents(latitude, longitude, radius),
    enabled: !!token,
  });
}

export function useMyEvents() {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', 'my'],
    queryFn: () => getMyEvents(),
    enabled: !!token,
  });
}

export function useEvent(id: string) {
  const { token } = useAuth();

  return useQuery({
    queryKey: ['events', id],
    queryFn: () => getEvent(id),
    enabled: !!token && !!id,
  });
}

export function useCreateEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: createEvent,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
    },
  });
}

export function useRsvpEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ 
      id, 
      status, 
      paymentMethod, 
      paymentMethodId 
    }: { 
      id: number; 
      status: string; 
      paymentMethod?: 'stripe' | 'token';
      paymentMethodId?: string;
    }) => rsvpEvent(id, status, paymentMethod, paymentMethodId),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['events', id.toString()] });
      queryClient.invalidateQueries({ queryKey: ['events', 'my'] });
    },
  });
}
