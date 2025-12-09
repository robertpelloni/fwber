import React, { createContext, useContext, ReactNode } from 'react';
import { useMercureLogic, MercureConnectionStatus, OnlineUser, PresenceUpdate, ChatMessage, TypingIndicator, NotificationPayload } from '@/lib/hooks/use-mercure-logic';

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
  connect: () => void;
  disconnect: () => void;
  sendChatMessage: (recipientId: string, content: string, type?: string) => Promise<void>;
  sendTypingIndicator: (recipientId: string, isTyping: boolean) => Promise<void>;
  updatePresence: (status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => Promise<void>;
  sendNotification: (recipientId: string, notification: any) => Promise<void>;
  isReady: boolean;
}

const MercureContext = createContext<MercureContextType | null>(null);

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
    throw new Error('useMercure must be used within a MercureProvider');
  }
  return context;
}
