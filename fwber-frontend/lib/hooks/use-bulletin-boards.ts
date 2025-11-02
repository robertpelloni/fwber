'use client';

import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';
import { useBulletinBoardAPI, BulletinBoard, BulletinMessage, LocationCoords } from '../api/bulletin-boards';
import { useAuth } from '../auth-context';
import { useState, useEffect, useRef } from 'react';

export function useBulletinBoards(filters: {
  lat: number;
  lng: number;
  radius?: number;
}) {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  
  return useQuery({
    queryKey: ['bulletin-boards', filters.lat, filters.lng, filters.radius],
    queryFn: () => api.getNearbyBoards(filters),
    enabled: !!token,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

export function useBulletinBoard(id: number, location?: LocationCoords) {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  
  return useQuery({
    queryKey: ['bulletin-board', id, location?.lat, location?.lng],
    queryFn: () => api.getBoard(id, location),
    enabled: !!token && !!id,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

export function useBulletinBoardMessages(boardId: number, options: {
  per_page?: number;
  since?: string;
} = {}) {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  
  return useQuery({
    queryKey: ['bulletin-board-messages', boardId, options.per_page, options.since],
    queryFn: () => api.getMessages(boardId, options),
    enabled: !!token && !!boardId,
    staleTime: 30 * 1000, // 30 seconds
  });
}

export function usePostMessage() {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({
      boardId,
      content,
      location,
      options = {}
    }: {
      boardId: number;
      content: string;
      location: LocationCoords;
      options?: {
        is_anonymous?: boolean;
        expires_in_hours?: number;
      };
    }) => {
      return api.postMessage(boardId, content, location, options);
    },
    onSuccess: (data, variables) => {
      // Optimistically update the messages cache
      queryClient.setQueryData(
        ['bulletin-board-messages', variables.boardId],
        (old: any) => {
          if (!old) return old;
          return {
            ...old,
            messages: {
              ...old.messages,
              data: [data.message, ...old.messages.data],
              total: old.messages.total + 1,
            }
          };
        }
      );
      
      // Invalidate and refetch nearby boards to update activity
      queryClient.invalidateQueries({
        queryKey: ['bulletin-boards'],
      });
    },
  });
}

export function useBulletinBoardSSE(boardId: number) {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  const queryClient = useQueryClient();
  const eventSourceRef = useRef<EventSource | null>(null);
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    if (!token || !boardId) return;

    // Clean up previous connection
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
    }

    // Create new EventSource connection
    const eventSource = api.createEventSource(boardId);
    eventSourceRef.current = eventSource;

    eventSource.onopen = () => {
      setIsConnected(true);
    };

    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        
        if (data.type === 'new_message') {
          // Update the messages cache with the new message
          queryClient.setQueryData(
            ['bulletin-board-messages', boardId],
            (old: any) => {
              if (!old) return old;
              
              // Check if message already exists to avoid duplicates
              const existingIndex = old.messages.data.findIndex(
                (msg: BulletinMessage) => msg.id === data.data.id
              );
              
              if (existingIndex >= 0) {
                // Update existing message
                const newData = [...old.messages.data];
                newData[existingIndex] = data.data;
                return {
                  ...old,
                  messages: {
                    ...old.messages,
                    data: newData,
                  }
                };
              } else {
                // Add new message
                return {
                  ...old,
                  messages: {
                    ...old.messages,
                    data: [data.data, ...old.messages.data],
                    total: old.messages.total + 1,
                  }
                };
              }
            }
          );
        } else if (data.type === 'connected') {
          console.log('Connected to bulletin board stream');
        }
      } catch (err) {
        console.error('Error parsing SSE message:', err);
      }
    };

    eventSource.onerror = (error) => {
      console.error('SSE connection error:', error);
      setIsConnected(false);
    };

    return () => {
      eventSource.close();
      setIsConnected(false);
    };
  }, [boardId, token, api, queryClient]);

  return { isConnected };
}

export function useCreateBulletinBoard() {
  const { token } = useAuth();
  const api = useBulletinBoardAPI();
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({
      location,
      radius
    }: {
      location: LocationCoords;
      radius?: number;
    }) => {
      return api.createOrFindBoard(location, radius);
    },
    onSuccess: (data) => {
      // Invalidate nearby boards to include the new board
      queryClient.invalidateQueries({
        queryKey: ['bulletin-boards'],
      });
    },
  });
}
