"use client";

import { createContext, useContext, ReactNode } from 'react';
import { usePusherLogic } from '@/lib/hooks/use-pusher-logic';

/**
 * RealtimeContext (formerly MercureContext)
 *
 * Provides global access to the real-time websocket connection (now powered by Pusher/Reverb).
 * This replaces the legacy Mercure context while maintaining a similar API structure for compatibility.
 */

// Define the shape of the context
interface RealtimeContextType {
  isConnected: boolean;
  connectionStatus: any;
  messages: any[];
  sendMessage: (recipientId: string, content: string) => Promise<void>;
  loadConversationHistory: (recipientId: string) => Promise<void>;
  // Add other methods exposed by usePusherLogic as needed
}

const RealtimeContext = createContext<any>(null);

export function MercureProvider({ children }: { children: ReactNode }) {
  // We now use Pusher logic under the hood instead of Mercure
  const realtime = usePusherLogic();

  return (
    <RealtimeContext.Provider value={realtime}>
      {children}
    </RealtimeContext.Provider>
  );
}

// Hook to consume the context
export function useMercure() {
  const context = useContext(RealtimeContext);
  if (!context) {
    throw new Error('useMercure must be used within a MercureProvider (which now wraps PusherLogic)');
  }
  return context;
}
