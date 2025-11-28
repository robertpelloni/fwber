import { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { createWebSocketClient, WebSocketClient, WebSocketMessage as WSMessage } from '@/lib/websocket/client';
import { logWebSocket } from '@/lib/logger';
import { storeMessage, updateMessageStatus, getConversationMessages } from '@/lib/messageStorage';

export interface WebSocketConnectionStatus {
  connected: boolean;
  connectionId: string | null;
  userId: string | null;
  reconnectAttempts: number;
}

export interface WebSocketMessage {
  type: string;
  data: any;
  timestamp: string;
  connection_id?: string;
  user_id?: string;
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

export interface UseWebSocketOptions {
  autoConnect?: boolean;
  heartbeatInterval?: number;
  maxReconnectAttempts?: number;
  reconnectDelay?: number;
  wsUrl?: string;
}

export function useWebSocketLogic(options: UseWebSocketOptions = {}) {
  const { user, isAuthenticated } = useAuth();
  const [client, setClient] = useState<WebSocketClient | null>(null);
  const [connectionStatus, setConnectionStatus] = useState<WebSocketConnectionStatus>({
    connected: false,
    connectionId: null,
    userId: null,
    reconnectAttempts: 0,
  });
  const [messages, setMessages] = useState<WebSocketMessage[]>([]);
  const [onlineUsers, setOnlineUsers] = useState<OnlineUser[]>([]);
  const [presenceUpdates, setPresenceUpdates] = useState<PresenceUpdate[]>([]);
  const [notifications, setNotifications] = useState<NotificationPayload[]>([]);
  const [chatMessages, setChatMessages] = useState<ChatMessage[]>([]);
  const [typingIndicators, setTypingIndicators] = useState<TypingIndicator[]>([]);

  const wsUrl = options.wsUrl || process.env.NEXT_PUBLIC_WS_URL || 'ws://localhost:8080';
  const token = user?.token || '';

  const handlersRef = useRef({
    handleConnection: (data: any) => {
      logWebSocket.connected(data.connectionId)
      setConnectionStatus(prev => ({
        ...prev,
        connected: true,
        connectionId: data.connectionId,
        userId: data.userId,
        reconnectAttempts: 0,
      }));
    },
    handleDisconnection: (data: any) => {
      logWebSocket.disconnected(data.reason)
      setConnectionStatus(prev => ({
        ...prev,
        connected: false,
      }));
    },
    handleReconnecting: (data: any) => {
      logWebSocket.reconnecting(data.attempt, data.maxAttempts, data.delay);
      setConnectionStatus(prev => ({
        ...prev,
        reconnectAttempts: data.attempt,
      }));
    },
    handleReconnectFailed: (data: any) => {
      logWebSocket.reconnectFailed(data.attempts);
    },
    handleMessage: (message: WebSocketMessage) => {
      console.log('WebSocket message received:', message);
      setMessages(prev => [...prev.slice(-99), message]);
    },
    handlePresenceUpdate: (data: PresenceUpdate) => {
      console.log('Presence update:', data);
      setPresenceUpdates(prev => [...prev.slice(-49), data]);

      if (data.status) {
        setOnlineUsers(prev => {
          const existingIndex = prev.findIndex(user => user.user_id === data.user_id);
          if (existingIndex >= 0) {
            const updated = [...prev];
            updated[existingIndex] = { ...updated[existingIndex], ...data };
            return updated;
          } else {
            return [...prev, { user_id: data.user_id, status: data.status, last_seen: data.timestamp, metadata: data.metadata }];
          }
        });
      }
    },
    handleNotification: (data: NotificationPayload) => {
      console.log('Notification received:', data);
      setNotifications(prev => [...prev.slice(-49), data]);
    },
    handleChatMessage: (data: ChatMessage) => {
      logWebSocket.messageReceived('chat_message', data.from_user_id)
      const messageWithStatus: ChatMessage = {
        ...data,
        status: 'delivered',
        delivered_at: new Date().toISOString(),
      };
      setChatMessages(prev => [...prev.slice(-99), messageWithStatus]);

      storeMessage(messageWithStatus).catch(err =>
        console.error('Failed to store message:', err)
      );
    },
    handleMessageDelivered: (data: { message_id: string; delivered_at: string }) => {
      setChatMessages(prev => prev.map(msg =>
        (msg.message_id === data.message_id || msg.id === data.message_id)
          ? { ...msg, status: 'delivered' as MessageStatus, delivered_at: data.delivered_at }
          : msg
      ));

      updateMessageStatus(data.message_id, 'delivered', data.delivered_at).catch(err =>
        console.error('Failed to update message status:', err)
      );
    },
    handleMessageRead: (data: { message_id: string; read_at: string }) => {
      setChatMessages(prev => prev.map(msg =>
        (msg.message_id === data.message_id || msg.id === data.message_id)
          ? { ...msg, status: 'read' as MessageStatus, read_at: data.read_at }
          : msg
      ));

      updateMessageStatus(data.message_id, 'read', data.read_at).catch(err =>
        console.error('Failed to update message status:', err)
      );
    },
    handleMessageFailed: (data: { messageId: string }) => {
      setChatMessages(prev => prev.map(msg =>
        (msg.message_id === data.messageId || msg.id === data.messageId)
          ? { ...msg, status: 'failed' as MessageStatus }
          : msg
      ));

      updateMessageStatus(data.messageId, 'failed').catch(err =>
        console.error('Failed to update message status:', err)
      );
    },
    handleTypingIndicator: (data: TypingIndicator) => {
      console.log('Typing indicator:', data);
      setTypingIndicators(prev => {
        const filtered = prev.filter(item =>
          !(item.from_user_id === data.from_user_id && item.to_user_id === data.to_user_id)
        );
        return [...filtered, data].slice(-20);
      });
    },
    handleError: (error: any) => {
      logWebSocket.error(error)
    },
  });

  useEffect(() => {
    if (isAuthenticated && user?.token && options.autoConnect !== false) {
      const handlers = handlersRef.current;

      const wsClient = createWebSocketClient(wsUrl, token, {
        autoConnect: true,
        heartbeatInterval: options.heartbeatInterval || 30000,
        maxReconnectAttempts: options.maxReconnectAttempts || 5,
        reconnectDelay: options.reconnectDelay || 1000,
      });

      setClient(wsClient);

      wsClient.on('connection', handlers.handleConnection);
      wsClient.on('disconnection', handlers.handleDisconnection);
      wsClient.on('reconnecting', handlers.handleReconnecting);
      wsClient.on('max_reconnect_attempts', handlers.handleReconnectFailed);
      wsClient.on('message', handlers.handleMessage);
      wsClient.on('presence_update', handlers.handlePresenceUpdate);
      wsClient.on('notification', handlers.handleNotification);
      wsClient.on('chat_message', handlers.handleChatMessage);
      wsClient.on('message_delivered', handlers.handleMessageDelivered);
      wsClient.on('message_read', handlers.handleMessageRead);
      wsClient.on('message_failed', handlers.handleMessageFailed);
      wsClient.on('typing_indicator', handlers.handleTypingIndicator);
      wsClient.on('error', handlers.handleError);

      return () => {
        wsClient.off('connection', handlers.handleConnection);
        wsClient.off('disconnection', handlers.handleDisconnection);
        wsClient.off('reconnecting', handlers.handleReconnecting);
        wsClient.off('max_reconnect_attempts', handlers.handleReconnectFailed);
        wsClient.off('message', handlers.handleMessage);
        wsClient.off('presence_update', handlers.handlePresenceUpdate);
        wsClient.off('notification', handlers.handleNotification);
        wsClient.off('chat_message', handlers.handleChatMessage);
        wsClient.off('message_delivered', handlers.handleMessageDelivered);
        wsClient.off('message_read', handlers.handleMessageRead);
        wsClient.off('message_failed', handlers.handleMessageFailed);
        wsClient.off('typing_indicator', handlers.handleTypingIndicator);
        wsClient.off('error', handlers.handleError);
        wsClient.disconnect();
      };
    }
  }, [
    isAuthenticated,
    user?.token,
    wsUrl,
    token,
    options.autoConnect,
    options.heartbeatInterval,
    options.maxReconnectAttempts,
    options.reconnectDelay,
  ]);

  const connect = useCallback(() => {
    if (client) {
      client.connect();
    }
  }, [client]);

  const disconnect = useCallback(() => {
    if (client) {
      client.disconnect();
    }
  }, [client]);

  const sendMessage = useCallback((message: WebSocketMessage) => {
    if (client) {
      client.send(message);
    }
  }, [client]);

  const sendChatMessage = useCallback((recipientId: string, content: string, type: string = 'text') => {
    if (client) {
      const messageId = client.sendChatMessage(recipientId, content, type);
      
      const optimisticMessage: ChatMessage = {
        message_id: messageId as string,
        from_user_id: user?.id || '',
        to_user_id: recipientId,
        content,
        message: { content, type, id: messageId as string },
        timestamp: new Date().toISOString(),
        status: 'sending',
      };
      
      setChatMessages(prev => [...prev.slice(-99), optimisticMessage]);
      
      storeMessage(optimisticMessage).catch(err =>
        console.error('Failed to store optimistic message:', err)
      );
      
      setTimeout(() => {
        setChatMessages(prev => prev.map(msg => 
          msg.message_id === messageId ? { ...msg, status: 'sent' as MessageStatus } : msg
        ));
        
        updateMessageStatus(messageId as string, 'sent').catch(err =>
          console.error('Failed to update sent status:', err)
        );
      }, 100);
    }
  }, [client, user]);

  const markMessageAsRead = useCallback((messageId: string) => {
    if (client) {
      client.send({
        type: 'message_read',
        data: { message_id: messageId, read_at: new Date().toISOString() },
        timestamp: new Date().toISOString(),
      });
      
      setChatMessages(prev => prev.map(msg => 
        (msg.message_id === messageId || msg.id === messageId)
          ? { ...msg, status: 'read' as MessageStatus, read_at: new Date().toISOString() }
          : msg
      ));
    }
  }, [client]);

  const sendTypingIndicator = useCallback((recipientId: string, isTyping: boolean) => {
    if (client) {
      client.sendTypingIndicator(recipientId, isTyping);
    }
  }, [client]);

  const updatePresence = useCallback((status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => {
    if (client) {
      client.updatePresence(status, metadata);
    }
  }, [client]);

  const sendNotification = useCallback((recipientId: string, notification: {
    title: string;
    body: string;
    type?: string;
    data?: Record<string, any>;
  }) => {
    if (client) {
      client.sendNotification(recipientId, notification);
    }
  }, [client]);

  const clearMessages = useCallback(() => {
    setMessages([]);
  }, []);

  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  const clearChatMessages = useCallback(() => {
    setChatMessages([]);
  }, []);

  const clearTypingIndicators = useCallback(() => {
    setTypingIndicators([]);
  }, []);

  const getConnectionStatus = useCallback(() => {
    return client?.getConnectionStatus() || connectionStatus;
  }, [client, connectionStatus]);

  const loadConversationHistory = useCallback(async (recipientId: string) => {
    if (!user?.id) return;
    
    try {
      const messages = await getConversationMessages(user.id, recipientId);
      setChatMessages(messages);
      return messages;
    } catch (error) {
      console.error('Failed to load conversation history:', error);
      return [];
    }
  }, [user]);

  return {
    connectionStatus,
    messages,
    onlineUsers,
    presenceUpdates,
    notifications,
    chatMessages,
    typingIndicators,
    connect,
    disconnect,
    sendMessage,
    sendChatMessage,
    sendTypingIndicator,
    updatePresence,
    sendNotification,
    markMessageAsRead,
    loadConversationHistory,
    clearMessages,
    clearNotifications,
    clearChatMessages,
    clearTypingIndicators,
    getConnectionStatus,
    client,
  };
}
