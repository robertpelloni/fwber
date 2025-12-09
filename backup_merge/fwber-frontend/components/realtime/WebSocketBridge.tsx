'use client';

import React from 'react';
import { useWebSocketContext, WebSocketProvider } from '@/lib/contexts/WebSocketContext';
import { PresenceProvider, WebSocketContextValue, OnlineUser, TypingIndicatorData } from './PresenceComponents';

// Re-export everything from PresenceComponents
export * from './PresenceComponents';

/**
 * Bridge component that connects WebSocketContext to PresenceComponents
 * 
 * Use this wrapper when you have WebSocketProvider in your tree and want
 * to use PresenceIndicator, TypingIndicator, etc. components.
 */
export function WebSocketPresenceProvider({ children }: { children: React.ReactNode }) {
  const wsContext = useWebSocketContext();
  
  // Map WebSocketContext to PresenceProvider format
  const presenceValue: WebSocketContextValue = {
    onlineUsers: wsContext.onlineUsers.map((u): OnlineUser => ({
      user_id: String(u.user_id),
      status: u.status || 'offline',
    })),
    typingIndicators: wsContext.typingIndicators.map((t): TypingIndicatorData => ({
      from_user_id: String(t.from_user_id),
      is_typing: t.is_typing,
    })),
    connectionStatus: {
      connected: wsContext.connectionStatus.connected,
      reconnectAttempts: wsContext.connectionStatus.reconnectAttempts,
    },
    sendTypingIndicator: wsContext.sendTypingIndicator,
  };

  return (
    <PresenceProvider value={presenceValue}>
      {children}
    </PresenceProvider>
  );
}

/**
 * Combined provider that includes both WebSocket and Presence context
 * 
 * Use this at the layout level to provide both WebSocket and Presence
 * functionality to all child components.
 */
export function RealtimeProvider({ 
  children,
  autoConnect = true,
  wsUrl,
}: { 
  children: React.ReactNode;
  autoConnect?: boolean;
  wsUrl?: string;
}) {
  return (
    <WebSocketProvider options={{ autoConnect, wsUrl }}>
      <WebSocketPresenceProvider>
        {children}
      </WebSocketPresenceProvider>
    </WebSocketProvider>
  );
}
