import { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api';

export interface MercureConnectionStatus {
  connected: boolean;
  connecting: boolean;
  error: Error | null;
}

export interface MercureMessage {
  id?: string;
  type: string;
  data: any;
  timestamp: string;
}

// Re-using interfaces from WebSocket logic for compatibility
export interface OnlineUser {
  user_id: string;
  status?: string;
  last_seen?: string;
  metadata?: Record<string, any>;
}

export interface PresenceUpdate {
  user_id: string;
  status: string;
  timestamp: string;
  metadata?: Record<string, any>;
}

export type MessageStatus = 'sending' | 'sent' | 'delivered' | 'read' | 'failed';

export interface ChatMessage {
  id?: string;
  message_id?: string;
  from_user_id: string;
  to_user_id: string;
  message?: { content?: string; type?: string; id?: string };
  content?: string;
  timestamp: string | number | Date;
  status?: MessageStatus;
  delivered_at?: string;
  read_at?: string;
  metadata?: Record<string, any>;
}

export interface TypingIndicator {
  from_user_id: string;
  to_user_id: string;
  is_typing: boolean;
  timestamp: string;
}

export interface NotificationPayload {
  id?: string;
  type: string;
  title?: string;
  message?: string;
  data?: any;
  timestamp: string;
  read?: boolean;
}

export function useMercureLogic(options: { autoConnect?: boolean } = {}) {
  const { user, isAuthenticated, token } = useAuth();
  const [status, setStatus] = useState<MercureConnectionStatus>({
    connected: false,
    connecting: false,
    error: null,
  });
  
  const [messages, setMessages] = useState<MercureMessage[]>([]);
  const [onlineUsers, setOnlineUsers] = useState<OnlineUser[]>([]);
  const [presenceUpdates, setPresenceUpdates] = useState<PresenceUpdate[]>([]);
  const [notifications, setNotifications] = useState<NotificationPayload[]>([]);
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>([]);
  const [typingIndicators, setTypingIndicators] = useState<TypingIndicator[]>([]);

  const eventSourceRef = useRef<EventSource | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const connect = useCallback(async () => {
    if (!isAuthenticated || !token) return;
    if (eventSourceRef.current?.readyState === EventSource.OPEN) return;

    try {
      setStatus(prev => ({ ...prev, connecting: true, error: null }));

      // 1. Get Mercure Token and Hub URL
      const response = await api.get<{ token: string; hub_url: string }>('/websocket/token');
      const { token: mercureToken, hub_url } = response;

      // 2. Construct URL with topics
      const url = new URL(hub_url);
      url.searchParams.append('topic', `https://fwber.me/user/${user?.id}`);
      url.searchParams.append('topic', 'https://fwber.me/presence');
      
      // Append authorization token
      url.searchParams.append('authorization', mercureToken);

      // 3. Create EventSource
      const es = new EventSource(url.toString());

      es.onopen = () => {
        console.log('Mercure connected');
        setStatus({ connected: true, connecting: false, error: null });
      };

      es.onerror = (e) => {
        console.error('Mercure error:', e);
        setStatus(prev => ({ ...prev, connected: false, error: new Error('Connection failed') }));
        es.close();
        
        // Simple reconnect logic
        if (reconnectTimeoutRef.current) clearTimeout(reconnectTimeoutRef.current);
        reconnectTimeoutRef.current = setTimeout(() => {
          connect();
        }, 3000);
      };

      es.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          handleMessage(data);
        } catch (err) {
          console.error('Failed to parse Mercure message:', err);
        }
      };

      eventSourceRef.current = es;

    } catch (err) {
      console.error('Failed to connect to Mercure:', err);
      setStatus({ connected: false, connecting: false, error: err as Error });
    }
  }, [isAuthenticated, token, user?.id]);

  const disconnect = useCallback(() => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }
    setStatus({ connected: false, connecting: false, error: null });
  }, []);

  const handleMessage = (data: any) => {
    // Dispatch based on message type
    // The backend sends messages with a 'type' field
    console.log('Mercure message received:', data);

    switch (data.type) {
      case 'chat_message':
        setChatMessages(prev => [...prev.slice(-99), data]);
        break;
      case 'presence_update':
        setPresenceUpdates(prev => [...prev.slice(-49), data]);
        if (data.status) {
            setOnlineUsers(prev => {
              const existingIndex = prev.findIndex(u => u.user_id === data.user_id);
              if (existingIndex >= 0) {
                const updated = [...prev];
                updated[existingIndex] = { ...updated[existingIndex], ...data };
                return updated;
              } else {
                return [...prev, { user_id: data.user_id, status: data.status, last_seen: data.timestamp, metadata: data.metadata }];
              }
            });
        }
        break;
      case 'notification':
        setNotifications(prev => [...prev.slice(-49), data.notification || data]);
        break;
      case 'typing_indicator':
        setTypingIndicators(prev => {
            const filtered = prev.filter(item =>
              !(item.from_user_id === data.from_user_id && item.to_user_id === data.to_user_id)
            );
            return [...filtered, data].slice(-20);
        });
        break;
      default:
        setMessages(prev => [...prev.slice(-99), data]);
    }
  };

  // API wrappers for sending data (since Mercure is one-way for client)
  const sendChatMessage = useCallback(async (recipientId: string, content: string, type: string = 'text') => {
    try {
      await api.post('/websocket/message', {
        recipient_id: recipientId,
        message: { type, content }
      });
      
      // Optimistic update could be added here
    } catch (err) {
      console.error('Failed to send message:', err);
    }
  }, []);

  const sendTypingIndicator = useCallback(async (recipientId: string, isTyping: boolean) => {
    try {
      await api.post('/websocket/typing', {
        recipient_id: recipientId,
        is_typing: isTyping
      });
    } catch (err) {
      console.error('Failed to send typing indicator:', err);
    }
  }, []);

  const updatePresence = useCallback(async (status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => {
    try {
      await api.post('/websocket/presence', { status, metadata });
    } catch (err) {
      console.error('Failed to update presence:', err);
    }
  }, []);

  const sendNotification = useCallback(async (recipientId: string, notification: any) => {
    try {
      await api.post('/websocket/notification', {
        recipient_id: recipientId,
        notification
      });
    } catch (err) {
      console.error('Failed to send notification:', err);
    }
  }, []);

  const loadConversationHistory = useCallback(async (recipientId: string) => {
    try {
      const response = await api.get<any>(`/messages/${recipientId}`);
      const history = (response.messages || response.data || []).map((msg: any) => ({
        id: msg.id,
        from_user_id: msg.sender_id,
        to_user_id: msg.receiver_id,
        content: msg.content,
        timestamp: msg.created_at,
        status: msg.read_at ? 'read' : 'delivered',
      }));
      setChatMessages(prev => {
        // Merge history with existing messages, avoiding duplicates
        const existingIds = new Set(prev.map(m => m.id));
        const newMessages = history.filter((m: any) => !existingIds.has(m.id));
        return [...newMessages, ...prev].sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime());
      });
    } catch (err) {
      console.error('Failed to load conversation history:', err);
    }
  }, []);

  const clearMessages = useCallback(() => setMessages([]), []);
  const clearNotifications = useCallback(() => setNotifications([]), []);

  useEffect(() => {
    if (options.autoConnect && isAuthenticated && !status.connected && !status.connecting) {
      connect();
    }
    return () => {
      disconnect();
    };
  }, [options.autoConnect, isAuthenticated, connect, disconnect]);

  return {
    connectionStatus: {
        connected: status.connected,
        connectionId: 'mercure', // Dummy ID
        userId: user?.id || null,
        reconnectAttempts: 0
    },
    messages,
    onlineUsers,
    presenceUpdates,
    notifications,
    chatMessages,
    typingIndicators,
    connect,
    disconnect,
    sendChatMessage,
    sendTypingIndicator,
    updatePresence,
    sendNotification,
    loadConversationHistory,
    clearMessages,
    clearNotifications,
    isReady: status.connected,
  };
}
