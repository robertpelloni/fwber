'use client';

import React from 'react';
import { useMercure, MercureProvider } from '@/lib/contexts/MercureContext';
import { PresenceProvider, WebSocketContextValue, OnlineUser, TypingIndicatorData } from './PresenceComponents';

/**
 * Bridge component that connects MercureContext to PresenceComponents
 */
export function MercurePresenceProvider({ children }: { children: React.ReactNode }) {
  const mercureContext = useMercure();
  
  // Map MercureContext to PresenceProvider format
  const presenceValue: WebSocketContextValue = {
    onlineUsers: mercureContext.onlineUsers.map((u): OnlineUser => ({
      user_id: String(u.user_id),
      status: u.status || 'offline',
    })),
    typingIndicators: mercureContext.typingIndicators.map((t): TypingIndicatorData => ({
      from_user_id: String(t.from_user_id),
      is_typing: t.is_typing,
    })),
    connectionStatus: {
      connected: mercureContext.connectionStatus.connected,
      reconnectAttempts: mercureContext.connectionStatus.reconnectAttempts,
    },
    sendTypingIndicator: mercureContext.sendTypingIndicator,
  };

  return (
    <PresenceProvider value={presenceValue}>
      {children}
    </PresenceProvider>
  );
}

/**
 * Combined provider that includes both Mercure and Presence context
 */
export function MercureRealtimeProvider({ 
  children,
  autoConnect = true,
}: { 
  children: React.ReactNode;
  autoConnect?: boolean;
}) {
  return (
    <MercureProvider>
      <MercurePresenceProvider>
        {children}
      </MercurePresenceProvider>
    </MercureProvider>
  );
}
