import { useCallback, useMemo, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useMercureSSE } from './use-mercure-sse';

/**
 * Subscribes to the public Local Pulse Mercure topic and invalidates
 * relevant React Query caches when artifact events arrive.
 */
export function useLocalPulseRealtime() {
  const queryClient = useQueryClient();
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  const invalidate = useCallback(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    // Debounce invalidation to avoid thrashing on bursts
    debounceRef.current = setTimeout(() => {
      queryClient.invalidateQueries({ queryKey: ['local-pulse'] });
      queryClient.invalidateQueries({ queryKey: ['proximity-artifacts'] });
    }, 500);
  }, [queryClient]);

  const onMessage = useCallback((data: any) => {
    // Expect: { type: 'artifact_created'|'artifact_flagged'|'artifact_removed', ... }
    if (!data || !data.type) return;
    switch (data.type) {
      case 'artifact_created':
      case 'artifact_flagged':
      case 'artifact_removed':
        invalidate();
        break;
      default:
        break;
    }
  }, [invalidate]);

  // Fixed public topic for Local Pulse updates
  const topics = useMemo(() => [`${process.env.NEXT_PUBLIC_APP_URL || 'https://fwber.me'}/public/local-pulse`], []);

  const { isConnected, error, connect, disconnect } = useMercureSSE({
    topics,
    onMessage,
    autoReconnect: true,
    reconnectInterval: 5000,
  });

  return { isConnected, error, connect, disconnect };
}
