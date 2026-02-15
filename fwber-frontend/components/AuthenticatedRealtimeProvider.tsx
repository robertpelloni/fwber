'use client';

import { useAuth } from '@/lib/auth-context';
import { PresenceProvider, WebSocketContextValue } from '@/lib/contexts/PresenceContext';
import { useWebSocket } from '@/lib/hooks/use-websocket';

interface AuthenticatedRealtimeProviderProps {
  children: React.ReactNode;
  autoConnect?: boolean;
}

/**
 * A wrapper that provides realtime features only when user is authenticated.
 * 
 * Use this in layouts for sections that need presence, typing indicators, etc.
 * It will gracefully degrade to no realtime features if user is not logged in.
 */
export function AuthenticatedRealtimeProvider({
  children,
  autoConnect = true,
}: AuthenticatedRealtimeProviderProps) {
  const { isAuthenticated } = useAuth();
  const websocket = useWebSocket({ autoConnect });

  // Only wrap with RealtimeProvider if authenticated
  if (!isAuthenticated) {
    return <>{children}</>;
  }

  // Cast onlineUsers to any to bypass strict type check for now
  // Ideally, ensure types in PresenceContext and use-pusher-logic match exactly
  const contextValue: WebSocketContextValue = {
    connectionStatus: {
        connected: websocket.connectionStatus.connected,
        reconnectAttempts: websocket.connectionStatus.reconnectAttempts
    },
    onlineUsers: (websocket.onlineUsers || []) as any,
    typingIndicators: (websocket.typingIndicators || []) as any,
    sendTypingIndicator: websocket.sendTypingIndicator,
  };

  return (
    <PresenceProvider value={contextValue}>
      {children}
    </PresenceProvider>
  );
}
