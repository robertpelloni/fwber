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

/**
 * Represents an online user in the WebSocket connection.
 * Tracks user presence status and metadata.
 */
export interface OnlineUser {
  /** Unique identifier for the user */
  user_id: string;
  /** Current presence status (online, away, busy, offline) */
  status?: string;
  /** ISO timestamp of last activity */
  last_seen?: string;
  /** Additional user metadata */
  metadata?: Record<string, any>;
}

/**
 * Represents a real-time presence status update.
 * Emitted when a user's online status changes.
 */
export interface PresenceUpdate {
  /** User who changed status */
  user_id: string;
  /** New presence status */
  status: string;
  /** ISO timestamp when status changed */
  timestamp: string;
  /** Additional context about the status change */
  metadata?: Record<string, any>;
}

/**
 * Message delivery and read status
 */
export type MessageStatus = 'sending' | 'sent' | 'delivered' | 'read' | 'failed';

/**
 * Represents a direct message between users.
 * Supports both structured message objects and plain content.
 */
export interface ChatMessage {
  /** Unique message identifier */
  id?: string;
  /** Message ID for tracking acknowledgments */
  message_id?: string;
  /** Sender's user ID */
  from_user_id: string;
  /** Recipient's user ID */
  to_user_id: string;
  /** Structured message object with content and type */
  message?: { content?: string; type?: string; id?: string };
  /** Plain text content (alternative to message object) */
  content?: string;
  /** When the message was sent */
  timestamp: string | number | Date;
  /** Delivery and read status */
  status?: MessageStatus;
  /** When the message was delivered */
  delivered_at?: string;
  /** When the message was read */
  read_at?: string;
  /** Additional message metadata (attachments, reactions, etc.) */
  metadata?: Record<string, any>;
}

/**
 * Represents a typing indicator in a chat conversation.
 * Shows when a user starts or stops typing.
 */
export interface TypingIndicator {
  /** User who is typing */
  from_user_id: string;
  /** User being typed to */
  to_user_id: string;
  /** Whether user is currently typing */
  is_typing: boolean;
  /** ISO timestamp of the typing event */
  timestamp: string;
}

/**
 * Represents a real-time notification payload.
 * Can be used for system alerts, user mentions, or custom events.
 */
export interface NotificationPayload {
  /** Unique notification identifier */
  id?: string;
  /** Notification category (mention, like, follow, system, etc.) */
  type: string;
  /** Notification title/heading */
  title?: string;
  /** Notification body text */
  message?: string;
  /** Additional structured data for the notification */
  data?: any;
  /** ISO timestamp when notification was created */
  timestamp: string;
  /** Whether the notification has been read by the user */
  read?: boolean;
}

export interface UseWebSocketOptions {
  autoConnect?: boolean;
  heartbeatInterval?: number;
  maxReconnectAttempts?: number;
  reconnectDelay?: number;
  wsUrl?: string;
}

export function useWebSocket(options: UseWebSocketOptions = {}) {
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

  // Event handlers (defined before useEffect to avoid hoisting issues)
  const handleConnection = useCallback((data: any) => {
    logWebSocket.connected(data.connectionId)
    setConnectionStatus(prev => ({
      ...prev,
      connected: true,
      connectionId: data.connectionId,
      userId: data.userId,
      reconnectAttempts: 0,
    }));
  }, []);

  const handleDisconnection = useCallback((data: any) => {
    logWebSocket.disconnected(data.reason)
    setConnectionStatus(prev => ({
      ...prev,
      connected: false,
    }));
  }, []);

  const handleReconnecting = useCallback((data: any) => {
    logWebSocket.reconnecting(data.attempt, data.maxAttempts, data.delay);
    setConnectionStatus(prev => ({
      ...prev,
      reconnectAttempts: data.attempt,
    }));
  }, []);

  const handleReconnectFailed = useCallback((data: any) => {
    logWebSocket.reconnectFailed(data.attempts);
  }, []);

  const handleMessage = useCallback((message: WebSocketMessage) => {
    console.log('WebSocket message received:', message);
    setMessages(prev => [...prev.slice(-99), message]); // Keep last 100 messages
  }, []);

  const handlePresenceUpdate = useCallback((data: PresenceUpdate) => {
    console.log('Presence update:', data);
    setPresenceUpdates(prev => [...prev.slice(-49), data]); // Keep last 50 updates
    
    // Update online users if this is a status change
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
  }, []);

  const handleNotification = useCallback((data: NotificationPayload) => {
    console.log('Notification received:', data);
    setNotifications(prev => [...prev.slice(-49), data]); // Keep last 50 notifications
  }, []);

  const handleChatMessage = useCallback((data: ChatMessage) => {
    logWebSocket.messageReceived('chat_message', data.from_user_id)
    // Set initial status for received messages
    const messageWithStatus: ChatMessage = {
      ...data,
      status: 'delivered',
      delivered_at: new Date().toISOString(),
    };
    setChatMessages(prev => [...prev.slice(-99), messageWithStatus]); // Keep last 100 messages
    
    // Store message in IndexedDB for offline access
    storeMessage(messageWithStatus).catch(err => 
      console.error('Failed to store message:', err)
    );
  }, []);

  const handleMessageDelivered = useCallback((data: { message_id: string; delivered_at: string }) => {
    setChatMessages(prev => prev.map(msg => 
      (msg.message_id === data.message_id || msg.id === data.message_id)
        ? { ...msg, status: 'delivered' as MessageStatus, delivered_at: data.delivered_at }
        : msg
    ));
    
    // Update status in IndexedDB
    updateMessageStatus(data.message_id, 'delivered', data.delivered_at).catch(err =>
      console.error('Failed to update message status:', err)
    );
  }, []);

  const handleMessageRead = useCallback((data: { message_id: string; read_at: string }) => {
    setChatMessages(prev => prev.map(msg => 
      (msg.message_id === data.message_id || msg.id === data.message_id)
        ? { ...msg, status: 'read' as MessageStatus, read_at: data.read_at }
        : msg
    ));
    
    // Update status in IndexedDB
    updateMessageStatus(data.message_id, 'read', data.read_at).catch(err =>
      console.error('Failed to update message status:', err)
    );
  }, []);

  const handleMessageFailed = useCallback((data: { messageId: string }) => {
    setChatMessages(prev => prev.map(msg => 
      (msg.message_id === data.messageId || msg.id === data.messageId)
        ? { ...msg, status: 'failed' as MessageStatus }
        : msg
    ));
    
    // Update status in IndexedDB
    updateMessageStatus(data.messageId, 'failed').catch(err =>
      console.error('Failed to update message status:', err)
    );
  }, []);

  const handleTypingIndicator = useCallback((data: TypingIndicator) => {
    console.log('Typing indicator:', data);
    setTypingIndicators(prev => {
      const filtered = prev.filter(item => 
        !(item.from_user_id === data.from_user_id && item.to_user_id === data.to_user_id)
      );
      return [...filtered, data].slice(-20); // Keep last 20 indicators
    });
  }, []);

  const handleError = useCallback((error: any) => {
    logWebSocket.error(error)
  }, []);

  // Initialize WebSocket client
  useEffect(() => {
    if (isAuthenticated && user?.token && options.autoConnect !== false) {
      const wsClient = createWebSocketClient(wsUrl, token, {
        autoConnect: true,
        heartbeatInterval: options.heartbeatInterval || 30000,
        maxReconnectAttempts: options.maxReconnectAttempts || 5,
        reconnectDelay: options.reconnectDelay || 1000,
      });

      setClient(wsClient);

      // Set up event listeners
      wsClient.on('connection', handleConnection);
      wsClient.on('disconnection', handleDisconnection);
      wsClient.on('reconnecting', handleReconnecting);
      wsClient.on('max_reconnect_attempts', handleReconnectFailed);
      wsClient.on('message', handleMessage);
      wsClient.on('presence_update', handlePresenceUpdate);
      wsClient.on('notification', handleNotification);
      wsClient.on('chat_message', handleChatMessage);
      wsClient.on('message_delivered', handleMessageDelivered);
      wsClient.on('message_read', handleMessageRead);
      wsClient.on('message_failed', handleMessageFailed);
      wsClient.on('typing_indicator', handleTypingIndicator);
      wsClient.on('error', handleError);

      return () => {
        wsClient.off('connection', handleConnection);
        wsClient.off('disconnection', handleDisconnection);
        wsClient.off('reconnecting', handleReconnecting);
        wsClient.off('max_reconnect_attempts', handleReconnectFailed);
        wsClient.off('message', handleMessage);
        wsClient.off('presence_update', handlePresenceUpdate);
        wsClient.off('notification', handleNotification);
        wsClient.off('chat_message', handleChatMessage);
        wsClient.off('message_delivered', handleMessageDelivered);
        wsClient.off('message_read', handleMessageRead);
        wsClient.off('message_failed', handleMessageFailed);
        wsClient.off('typing_indicator', handleTypingIndicator);
        wsClient.off('error', handleError);
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
    handleConnection,
    handleDisconnection,
    handleReconnecting,
    handleReconnectFailed,
    handleMessage,
    handlePresenceUpdate,
    handleNotification,
    handleChatMessage,
    handleMessageDelivered,
    handleMessageRead,
    handleMessageFailed,
    handleTypingIndicator,
    handleError,
  ]);

  // WebSocket actions
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
      
      // Add optimistic message with 'sending' status
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
      
      // Store optimistic message in IndexedDB
      storeMessage(optimisticMessage).catch(err =>
        console.error('Failed to store optimistic message:', err)
      );
      
      // Update to 'sent' after a brief delay (will be updated to 'delivered' when server confirms)
      setTimeout(() => {
        setChatMessages(prev => prev.map(msg => 
          msg.message_id === messageId ? { ...msg, status: 'sent' as MessageStatus } : msg
        ));
        
        // Update status in IndexedDB
        updateMessageStatus(messageId as string, 'sent').catch(err =>
          console.error('Failed to update sent status:', err)
        );
      }, 100);
    }
  }, [client, user]);

  const markMessageAsRead = useCallback((messageId: string) => {
    if (client) {
      // Send read receipt to server
      client.send({
        type: 'message_read',
        data: { message_id: messageId, read_at: new Date().toISOString() },
        timestamp: new Date().toISOString(),
      });
      
      // Update local state
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

  // Utility functions
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

  // Load conversation history from IndexedDB
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
    // State
    connectionStatus,
    messages,
    onlineUsers,
    presenceUpdates,
    notifications,
    chatMessages,
    typingIndicators,
    
    // Actions
    connect,
    disconnect,
    sendMessage,
    sendChatMessage,
    sendTypingIndicator,
    updatePresence,
    sendNotification,
    markMessageAsRead,
    loadConversationHistory,
    
    // Utilities
    clearMessages,
    clearNotifications,
    clearChatMessages,
    clearTypingIndicators,
    getConnectionStatus,
    
    // Client instance
    client,
  };
}

/**
 * Hook for WebSocket chat functionality
 */
export function useWebSocketChat(recipientId?: string) {
  const { sendChatMessage, sendTypingIndicator, chatMessages, typingIndicators, onlineUsers } = useWebSocket();
  
  const [isTyping, setIsTyping] = useState(false);
  const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Filter messages for specific recipient
  const recipientMessages = chatMessages.filter(msg => 
    (msg.from_user_id === recipientId || msg.to_user_id === recipientId) ||
    (msg.from_user_id === recipientId || msg.to_user_id === recipientId)
  );

  // Filter typing indicators for specific recipient
  const recipientTyping = typingIndicators.filter(indicator => 
    (indicator.from_user_id === recipientId || indicator.to_user_id === recipientId)
  );

  const handleSendMessage = useCallback((content: string, type: string = 'text') => {
    if (recipientId) {
      sendChatMessage(recipientId, content, type);
    }
  }, [sendChatMessage, recipientId]);

  const handleStartTyping = useCallback(() => {
    if (recipientId && !isTyping) {
      setIsTyping(true);
      sendTypingIndicator(recipientId, true);
    }
  }, [sendTypingIndicator, recipientId, isTyping]);

  const handleStopTyping = useCallback(() => {
    if (recipientId && isTyping) {
      setIsTyping(false);
      sendTypingIndicator(recipientId, false);
    }
  }, [sendTypingIndicator, recipientId, isTyping]);

  const handleTypingChange = useCallback((value: string) => {
    if (value.length > 0) {
      handleStartTyping();
      
      // Clear existing timeout
      if (typingTimeoutRef.current) {
        clearTimeout(typingTimeoutRef.current);
      }
      
      // Set new timeout to stop typing
      typingTimeoutRef.current = setTimeout(() => {
        handleStopTyping();
      }, 3000);
    } else {
      handleStopTyping();
    }
  }, [handleStartTyping, handleStopTyping]);

  // Cleanup typing timeout on unmount
  useEffect(() => {
    return () => {
      if (typingTimeoutRef.current) {
        clearTimeout(typingTimeoutRef.current);
      }
    };
  }, []);

  return {
    messages: recipientMessages,
    typingIndicators: recipientTyping,
    sendMessage: handleSendMessage,
    handleTypingChange,
    isTyping,
    chatMessages, // Add for ChatList component
    onlineUsers, // Add for OnlineUsers component
  };
}

/**
 * Hook for WebSocket presence functionality
 */
export function useWebSocketPresence() {
  const { updatePresence, onlineUsers, presenceUpdates } = useWebSocket();
  
  const [currentStatus, setCurrentStatus] = useState<'online' | 'away' | 'busy' | 'offline'>('online');

  const setStatus = useCallback((status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>) => {
    setCurrentStatus(status);
    updatePresence(status, metadata);
  }, [updatePresence]);

  const setOnline = useCallback((metadata?: Record<string, any>) => {
    setStatus('online', metadata);
  }, [setStatus]);

  const setAway = useCallback((metadata?: Record<string, any>) => {
    setStatus('away', metadata);
  }, [setStatus]);

  const setBusy = useCallback((metadata?: Record<string, any>) => {
    setStatus('busy', metadata);
  }, [setStatus]);

  const setOffline = useCallback((metadata?: Record<string, any>) => {
    setStatus('offline', metadata);
  }, [setStatus]);

  return {
    currentStatus,
    onlineUsers,
    presenceUpdates,
    setStatus,
    setOnline,
    setAway,
    setBusy,
    setOffline,
  };
}

/**
 * Hook for WebSocket notifications
 */
export function useWebSocketNotifications() {
  const { notifications, clearNotifications } = useWebSocket();
  
  const [unreadCount, setUnreadCount] = useState(0);

  // Count unread notifications
  useEffect(() => {
    const unread = notifications.filter(notification => !notification.read);
    setUnreadCount(unread.length);
  }, [notifications]);

  const markAsRead = useCallback((notificationId: string) => {
    // This would typically make an API call to mark as read
    console.log('Marking notification as read:', notificationId);
  }, []);

  const markAllAsRead = useCallback(() => {
    // This would typically make an API call to mark all as read
    console.log('Marking all notifications as read');
  }, []);

  return {
    notifications,
    unreadCount,
    clearNotifications,
    markAsRead,
    markAllAsRead,
  };
}
