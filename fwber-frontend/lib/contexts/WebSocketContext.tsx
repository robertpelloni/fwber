'use client';

import React, { createContext, useContext, ReactNode } from 'react';
import { useWebSocketLogic, UseWebSocketOptions, WebSocketConnectionStatus, WebSocketMessage, OnlineUser, PresenceUpdate, NotificationPayload, ChatMessage, TypingIndicator, MessageStatus } from '@/lib/hooks/use-websocket-logic';
import { WebSocketClient } from '@/lib/websocket/client';

interface WebSocketContextType {
  connectionStatus: WebSocketConnectionStatus;
  messages: WebSocketMessage[];
  onlineUsers: OnlineUser[];
  presenceUpdates: PresenceUpdate[];
  notifications: NotificationPayload[];
  chatMessages: ChatMessage[];
  typingIndicators: TypingIndicator[];
  connect: () => void;
  disconnect: () => void;
  sendMessage: (message: WebSocketMessage) => void;
  sendChatMessage: (recipientId: string, content: string, type?: string) => void;
  sendTypingIndicator: (recipientId: string, isTyping: boolean) => void;
  updatePresence: (status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => void;
  sendNotification: (recipientId: string, notification: { title: string; body: string; type?: string; data?: Record<string, any> }) => void;
  markMessageAsRead: (messageId: string) => void;
  loadConversationHistory: (recipientId: string) => Promise<ChatMessage[] | undefined>;
  clearMessages: () => void;
  clearNotifications: () => void;
  clearChatMessages: () => void;
  clearTypingIndicators: () => void;
  getConnectionStatus: () => WebSocketConnectionStatus;
  client: WebSocketClient | null;
}

const WebSocketContext = createContext<WebSocketContextType | undefined>(undefined);

export function WebSocketProvider({ children, options }: { children: ReactNode; options?: UseWebSocketOptions }) {
  const webSocket = useWebSocketLogic(options);

  return (
    <WebSocketContext.Provider value={webSocket}>
      {children}
    </WebSocketContext.Provider>
  );
}

export function useWebSocketContext() {
  const context = useContext(WebSocketContext);
  if (context === undefined) {
    throw new Error('useWebSocketContext must be used within a WebSocketProvider');
  }
  return context;
}
