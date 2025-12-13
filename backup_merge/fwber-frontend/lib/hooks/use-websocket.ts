import { useState, useEffect, useCallback, useRef } from 'react';
import { useMercure } from '@/lib/contexts/MercureContext';
import { useMercureLogic } from './use-mercure-logic';
import { UseWebSocketOptions } from './use-websocket-logic';

// Re-export types
export * from './use-websocket-logic';

/**
 * Hook to access the Realtime (Mercure) context.
 * If used outside of a MercureProvider, it falls back to creating a local instance.
 */
export function useWebSocket(options: UseWebSocketOptions = {}) {
  try {
    return useMercure();
  } catch (e) {
    // Fallback for components not wrapped in Provider
    console.warn('useWebSocket used outside of MercureProvider. Creating local instance.');
    // eslint-disable-next-line react-hooks/rules-of-hooks
    return useMercureLogic(options);
  }
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
