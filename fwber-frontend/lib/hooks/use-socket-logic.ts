import { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { io, Socket } from 'socket.io-client';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { logWebSocket } from '@/lib/logger';

// Import shared types
import type {
  OnlineUser,
  PresenceUpdate,
  ChatMessage,
  TypingIndicator,
  NotificationPayload,
} from '@/lib/types/realtime';

export function useSocketLogic(options: { autoConnect?: boolean } = {}) {
  const { user, isAuthenticated, token } = useAuth();
  const [connected, setConnected] = useState(false);
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>([]);
  const [typingIndicators, setTypingIndicators] = useState<TypingIndicator[]>([]);
  const socketRef = useRef<Socket | null>(null);

  const connect = useCallback(() => {
    if (socketRef.current || !token) return;

    const socketUrl = process.env.NEXT_PUBLIC_WS_URL || process.env.NEXT_PUBLIC_API_URL?.replace(/\/api$/, '') || 'http://localhost:4000';
    
    const socket = io(socketUrl, {
      auth: { token },
      transports: ['websocket'],
      reconnectionAttempts: 5,
      reconnectionDelay: 2000,
      reconnectionDelayMax: 30000,
      timeout: 10000,
    });

    socket.on('connect', () => {
      setConnected(true);
      console.log('[Socket.io] Connected to server');
    });

    socket.on('disconnect', (reason) => {
      setConnected(false);
      console.log('[Socket.io] Disconnected from server:', reason);
    });

    socket.on('connect_error', (error) => {
      console.warn('[Socket.io] Connection error:', error.message);
      // Don't log full errors to avoid noise from expected failures
    });

    socket.on('new_message', (data: any) => {
      logWebSocket.messageReceived('chat_message', String(data.senderId));
      const newMessage: ChatMessage = {
        id: data.id,
        from_user_id: String(data.senderId),
        to_user_id: String(user?.id),
        content: data.content,
        timestamp: data.createdAt,
        status: 'delivered',
      };
      setChatMessages(prev => [...prev, newMessage]);
    });

    socket.on('user_typing', (data: any) => {
      const indicator: TypingIndicator = {
        from_user_id: String(data.senderId),
        to_user_id: String(user?.id),
        is_typing: true,
      };
      setTypingIndicators(prev => {
        const filtered = prev.filter(i => i.from_user_id !== indicator.from_user_id);
        return [...filtered, indicator];
      });

      // Auto-remove typing indicator after 3 seconds
      setTimeout(() => {
        setTypingIndicators(prev => prev.filter(i => i.from_user_id !== indicator.from_user_id));
      }, 3000);
    });

    socketRef.current = socket;
  }, [token, user?.id]);

  const disconnect = useCallback(() => {
    if (socketRef.current) {
      socketRef.current.disconnect();
      socketRef.current = null;
    }
    setConnected(false);
  }, []);

  const sendChatMessage = useCallback((receiverId: string, content: string) => {
    if (socketRef.current) {
      socketRef.current.emit('send_message', { 
        receiverId: parseInt(receiverId), 
        content 
      });

      // Optimistic update
      const optimisticMsg: ChatMessage = {
        id: `temp-${Date.now()}`,
        from_user_id: String(user?.id),
        to_user_id: receiverId,
        content: content,
        timestamp: new Date().toISOString(),
        status: 'sending',
      };
      setChatMessages(prev => [...prev, optimisticMsg]);
    }
  }, [user?.id]);

  const sendTypingIndicator = useCallback((receiverId: string) => {
    if (socketRef.current) {
      socketRef.current.emit('typing', { 
        receiverId: parseInt(receiverId) 
      });
    }
  }, []);

  useEffect(() => {
    if (options.autoConnect && isAuthenticated) {
      connect();
    }
    return () => disconnect();
  }, [options.autoConnect, isAuthenticated, connect, disconnect]);

  return {
    connected,
    chatMessages,
    typingIndicators,
    sendChatMessage,
    sendTypingIndicator,
    connect,
    disconnect,
  };
}
