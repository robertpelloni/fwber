import { useCallback, useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useWebSocket } from './use-websocket';

/**
 * Subscribes to Local Pulse updates via WebSocket and invalidates
 * relevant React Query caches when artifact events arrive.
 */
export function useLocalPulseRealtime() {
  const queryClient = useQueryClient();
  const debounceRef = useRef<NodeJS.Timeout | null>(null);
  const { messages, connectionStatus } = useWebSocket();

  const invalidate = useCallback(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    // Debounce invalidation to avoid thrashing on bursts
    debounceRef.current = setTimeout(() => {
      queryClient.invalidateQueries({ queryKey: ['local-pulse'] });
      queryClient.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    }, 500);
  }, [queryClient]);

  useEffect(() => {
    // Process latest messages
    // Ideally we'd process new messages only, but for now we scan the list.
    // Optimization: Check the last message or use a specialized hook listener if available.
    // Since 'messages' in useWebSocket is a list, we might re-process old ones if we aren't careful.
    // However, for this simple logic, we can just look at the last message if it changed.

    if (messages.length === 0) return;
    const lastMsg = messages[messages.length - 1];

    if (['artifact_created', 'artifact_flagged', 'artifact_removed'].includes(lastMsg.type)) {
        invalidate();
    }
  }, [messages, invalidate]);

  return { isConnected: connectionStatus.connected, error: null };
}
