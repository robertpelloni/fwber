import { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { storeOfflineChatMessage } from '@/lib/offline-store';
import { useE2EEncryption } from '@/lib/hooks/use-e2e-encryption';
import { initEcho } from '@/lib/echo';

// Re-using interfaces from Mercure logic for compatibility
export interface ConnectionStatus {
  connected: boolean;
  connecting: boolean;
  error: Error | null;
}

export interface Message {
  id?: string;
  type: string;
  data: any;
  timestamp: string;
}

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
  message_type?: string;
  media_url?: string;
  media_duration?: number;
  is_encrypted?: boolean;
  transcription?: string;
}

export interface TypingIndicator {
  from_user_id: string;
  to_user_id?: string;
  chatroom_id?: string;
  is_typing: boolean;
  timestamp: string;
}

export interface VideoSignal {
  from_user_id: string;
  signal: any;
  call_id?: number;
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
  [key: string]: any;
}

export function usePusherLogic(options: { autoConnect?: boolean } = {}) {
  const { user, isAuthenticated, token } = useAuth();
  const { encrypt, decrypt, isReady: isE2EReady } = useE2EEncryption();
  const [status, setStatus] = useState<ConnectionStatus>({
    connected: false,
    connecting: false,
    error: null,
  });
  
  const [messages, setMessages] = useState<Message[]>([]);
  const [onlineUsers, setOnlineUsers] = useState<OnlineUser[]>([]);
  const [presenceUpdates, setPresenceUpdates] = useState<PresenceUpdate[]>([]);
  const [notifications, setNotifications] = useState<NotificationPayload[]>([]);
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>([]);
  const [typingIndicators, setTypingIndicators] = useState<TypingIndicator[]>([]);
  const [videoSignals, setVideoSignals] = useState<VideoSignal[]>([]);

  const echoRef = useRef<any>(null);
  
  // Refs for E2E to avoid stale closures in callbacks
  const decryptRef = useRef(decrypt);
  const encryptRef = useRef(encrypt);
  const isE2EReadyRef = useRef(isE2EReady);
  
  useEffect(() => { decryptRef.current = decrypt; }, [decrypt]);
  useEffect(() => { encryptRef.current = encrypt; }, [encrypt]);
  useEffect(() => { isE2EReadyRef.current = isE2EReady; }, [isE2EReady]);

  const handleMessage = async (data: any) => {
    console.log('Pusher message received:', data);

    // Decrypt if needed
    if (data.type === 'chat_message' && data.is_encrypted && isE2EReadyRef.current) {
        try {
            const peerId = parseInt(data.from_user_id);
            if (!isNaN(peerId)) {
                const decrypted = await decryptRef.current(peerId, data.content);
                data.content = decrypted;
                data.is_encrypted = false;
            }
        } catch (e) {
            console.error('Decryption failed for incoming message', e);
            data.content = '🔒 Encrypted Message (Decryption Failed)';
        }
    }

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
            const filtered = prev.filter(item => {
              if (data.chatroom_id) {
                return !(item.from_user_id === data.from_user_id && item.chatroom_id === data.chatroom_id);
              }
              return !(item.from_user_id === data.from_user_id && item.to_user_id === data.to_user_id);
            });
            return [...filtered, data].slice(-20);
        });
        break;
      case 'video_signal':
        setVideoSignals(prev => [...prev, data]);
        break;
      default:
        setMessages(prev => [...prev.slice(-99), data]);
    }
  };

  const connect = useCallback(async () => {
    if (echoRef.current || !user?.id || !token) return;

    setStatus(prev => ({ ...prev, connecting: true, error: null }));

    try {
        const echo = initEcho(token);
        if (!echo) {
            throw new Error('Failed to initialize Echo');
        }

        echoRef.current = echo;

        // Subscribe to private user channel
        echo.private(`users.${user.id}`)
            .listen('.ChatMessageSent', (e: any) => handleMessage({ ...e, type: 'chat_message' }))
            .listen('.NotificationSent', (e: any) => handleMessage({ ...e, type: 'notification' }))
            .listen('.TypingIndicator', (e: any) => handleMessage({ ...e, type: 'typing_indicator' }))
            .listen('.VideoSignal', (e: any) => handleMessage({ ...e, type: 'video_signal' }));

        // Subscribe to presence channel (if needed)
        // echo.join('online')
        //     .here((users: any) => setOnlineUsers(users))
        //     .joining((user: any) => console.log(user.name))
        //     .leaving((user: any) => console.log(user.name));

        setStatus({ connected: true, connecting: false, error: null });

        (echo.connector as any).pusher.connection.bind('connected', () => {
            setStatus({ connected: true, connecting: false, error: null });
        });

        (echo.connector as any).pusher.connection.bind('disconnected', () => {
            setStatus({ connected: false, connecting: false, error: null });
        });

        (echo.connector as any).pusher.connection.bind('error', (err: any) => {
            console.error('Pusher connection error:', err);
            setStatus({ connected: false, connecting: false, error: err });
        });

    } catch (e) {
        console.error('Failed to connect to Pusher', e);
        setStatus({ connected: false, connecting: false, error: e as Error });
    }
  }, [user?.id, token]);

  const disconnect = useCallback(() => {
    if (echoRef.current) {
      echoRef.current.disconnect();
      echoRef.current = null;
    }
    setStatus({ connected: false, connecting: false, error: null });
  }, []);

  // API wrappers for sending data
  const sendChatMessage = useCallback(async (recipientId: string, content: string, type: string = 'text') => {
    try {
      if (!navigator.onLine) {
        throw new Error('Offline');
      }

      let finalContent = content;
      let isEncrypted = false;

      if (type === 'text' && isE2EReadyRef.current) {
        try {
            finalContent = await encryptRef.current(parseInt(recipientId), content);
            isEncrypted = true;
        } catch (e) {
            console.error('Encryption failed, falling back to plain text', e);
        }
      }

      await api.post('/websocket/message', {
        recipient_id: recipientId,
        message: { type, content: finalContent, is_encrypted: isEncrypted }
      });
      
    } catch (err) {
      console.error('Failed to send message:', err);
      
      // Offline fallback
      if (!navigator.onLine || (err as any)?.message === 'Offline' || (err as any)?.code === 'ERR_NETWORK') {
        try {
          await storeOfflineChatMessage({
            recipient_id: recipientId,
            message: { type, content },
            token: token,
            timestamp: new Date().toISOString()
          });
          
          // Register background sync
          if ('serviceWorker' in navigator && 'SyncManager' in window) {
            const registration = await navigator.serviceWorker.ready;
            // @ts-ignore
            await registration.sync.register('chat-message');
          }
          
          // Optimistic update
          const offlineMsg: ChatMessage = {
            id: `offline-${Date.now()}`,
            from_user_id: user?.id || '',
            to_user_id: recipientId,
            content: content,
            timestamp: new Date().toISOString(),
            status: 'sending',
            message_type: type
          };
          setChatMessages(prev => [...prev, offlineMsg]);
          
        } catch (storeErr) {
          console.error('Failed to store offline message:', storeErr);
        }
      }
    }
  }, [token, user?.id]);

  const sendTypingIndicator = useCallback(async (recipientId: string, isTyping: boolean, chatroomId?: string) => {
    try {
      await api.post('/websocket/typing', {
        recipient_id: recipientId,
        chatroom_id: chatroomId,
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

  const sendVideoSignal = useCallback(async (recipientId: string, signal: any, callId?: number) => {
    try {
      await api.post('/video/signal', { recipient_id: recipientId, signal, call_id: callId });
    } catch (err) {
      console.error('Failed to send video signal:', err);
    }
  }, []);

  const loadConversationHistory = useCallback(async (recipientId: string) => {
    try {
      const response = await api.get<any>(`/messages/${recipientId}`);
      const history = await Promise.all((response.messages || response.data || []).map(async (msg: any) => {
        let content = msg.content;
        if (msg.is_encrypted && isE2EReadyRef.current) {
            try {
                const peerId = msg.sender_id === user?.id ? msg.receiver_id : msg.sender_id;
                content = await decryptRef.current(peerId, content);
            } catch (e) {
                console.error('Failed to decrypt history message', e);
                content = '🔒 Encrypted Message';
            }
        }

        return {
            id: msg.id,
            from_user_id: msg.sender_id,
            to_user_id: msg.receiver_id,
            content: content,
            timestamp: msg.created_at,
            status: msg.read_at ? 'read' : 'delivered',
            message_type: msg.message_type,
            media_url: msg.media_url,
            media_duration: msg.media_duration,
            is_encrypted: false
        };
      }));

      setChatMessages(prev => {
        const existingIds = new Set(prev.map(m => m.id));
        const newMessages = history.filter((m: any) => !existingIds.has(m.id));
        return [...newMessages, ...prev].sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime());
      });
    } catch (err) {
      console.error('Failed to load conversation history:', err);
    }
  }, [user?.id]);

  const clearMessages = useCallback(() => setMessages([]), []);
  const clearNotifications = useCallback(() => setNotifications([]), []);

  useEffect(() => {
    if (options.autoConnect && isAuthenticated) {
      connect();
    }
    return () => {
      disconnect();
    };
  }, [options.autoConnect, isAuthenticated, connect, disconnect]);

  return useMemo(() => ({
    connectionStatus: {
        connected: status.connected,
        connectionId: 'pusher',
        userId: user?.id || null,
        reconnectAttempts: 0
    },
    messages,
    onlineUsers,
    presenceUpdates,
    notifications,
    chatMessages,
    typingIndicators,
    videoSignals,
    connect,
    disconnect,
    sendChatMessage,
    sendTypingIndicator,
    sendVideoSignal,
    updatePresence,
    sendNotification,
    loadConversationHistory,
    clearMessages,
    clearNotifications,
    isReady: !status.connecting,
    echo: echoRef.current,
  }), [
    status.connected,
    status.connecting,
    user?.id,
    messages,
    onlineUsers,
    presenceUpdates,
    notifications,
    chatMessages,
    typingIndicators,
    videoSignals,
    connect,
    disconnect,
    sendChatMessage,
    sendTypingIndicator,
    sendVideoSignal,
    updatePresence,
    sendNotification,
    loadConversationHistory,
    clearMessages,
    clearNotifications
  ]);
}
