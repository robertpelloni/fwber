'use client';

import React, { createContext, useContext } from 'react';

// Type definitions for WebSocket context
export interface OnlineUser {
  user_id: string;
  status: string;
}

export interface TypingIndicatorData {
  from_user_id: string;
  chatroom_id?: string;
  is_typing: boolean;
}

export interface WebSocketContextValue {
  onlineUsers?: OnlineUser[];
  typingIndicators?: TypingIndicatorData[];
  connectionStatus: {
    connected: boolean;
    reconnectAttempts: number;
  };
  sendTypingIndicator?: (recipientId: string, isTyping: boolean, chatroomId?: string) => void;
}

// Default context value when not in provider
const defaultContextValue: WebSocketContextValue = {
  onlineUsers: [],
  typingIndicators: [],
  connectionStatus: {
    connected: false,
    reconnectAttempts: 0,
  },
};

// Create the context
export const PresenceContext = createContext<WebSocketContextValue>(defaultContextValue);

// Export a provider wrapper that can be used to inject context
export function PresenceProvider({ 
  children, 
  value 
}: { 
  children: React.ReactNode; 
  value?: WebSocketContextValue;
}) {
  return (
    <PresenceContext.Provider value={value || defaultContextValue}>
      {children}
    </PresenceContext.Provider>
  );
}

// Hook to use the presence context
export function usePresenceContext(): WebSocketContextValue {
  const context = useContext(PresenceContext);
  return context || defaultContextValue;
}
