'use client';

import React, { createContext, useContext, ReactNode } from 'react';
import { useMercureLogic, MercureConnectionStatus, OnlineUser, PresenceUpdate, ChatMessage, TypingIndicator, NotificationPayload, VideoSignal } from '@/lib/hooks/use-mercure-logic';

interface MercureContextType {
  connectionStatus: {
      connected: boolean;
      connectionId: string | null;
      userId: string | null;
      reconnectAttempts: number;
  };
  messages: any[];
  onlineUsers: OnlineUser[];
  presenceUpdates: PresenceUpdate[];
  notifications: NotificationPayload[];
  chatMessages: ChatMessage[];
  typingIndicators: TypingIndicator[];
  videoSignals: VideoSignal[];
  connect: () => void;
  disconnect: () => void;
  sendChatMessage: (recipientId: string, content: string, type?: string) => Promise<void>;
  sendTypingIndicator: (recipientId: string, isTyping: boolean, chatroomId?: string) => Promise<void>;
  sendVideoSignal: (recipientId: string, signal: any, callId?: number) => Promise<void>;
  updatePresence: (status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => Promise<void>;
  sendNotification: (recipientId: string, notification: any) => Promise<void>;
  loadConversationHistory: (recipientId: string) => Promise<void>;
  clearMessages: () => void;
  clearNotifications: () => void;
  isReady: boolean;
}

export const MercureContext = createContext<MercureContextType | null>(null);

export function MercureProvider({ children }: { children: ReactNode }) {
  const mercure = useMercureLogic({ autoConnect: true });

  return (
    <MercureContext.Provider value={mercure}>
      {children}
    </MercureContext.Provider>
  );
}

export function useMercure() {
  const context = useContext(MercureContext);
  if (!context) {
    // Return a dummy context to prevent build failures during static generation
    // where the provider might not be available in the tree.
    if (typeof window === 'undefined') {
      return {
        connectionStatus: { connected: false, connectionId: null, userId: null, reconnectAttempts: 0 },
        messages: [],
        onlineUsers: [],
        presenceUpdates: [],
        notifications: [],
        chatMessages: [],
        typingIndicators: [],
        videoSignals: [],
        connect: () => {},
        disconnect: () => {},
        sendChatMessage: async () => {},
        sendTypingIndicator: async () => {},
        sendVideoSignal: async () => {},
        updatePresence: async () => {},
        sendNotification: async () => {},
        loadConversationHistory: async () => {},
        clearMessages: () => {},
        clearNotifications: () => {},
        isReady: false,
      };
    }
    throw new Error('useMercure must be used within a MercureProvider');
  }
  return context;
}
