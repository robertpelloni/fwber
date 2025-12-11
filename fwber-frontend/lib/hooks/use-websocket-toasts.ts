import { useEffect, useRef } from 'react';
import { useWebSocket } from './use-websocket';
import { useToast } from '@/components/ToastProvider';
import { useRouter } from 'next/navigation';

/**
 * Hook to automatically show toast notifications for WebSocket events
 * Integrates WebSocket events with the toast notification system
 */
export function useWebSocketToasts() {
  const { chatMessages, onlineUsers, connectionStatus } = useWebSocket();
  const { showMessage, showMatch, showSuccess, showError } = useToast();
  const router = useRouter();
  const lastMessageIdRef = useRef<string | null>(null);
  const lastOnlineCountRef = useRef<number>(0);

  // Show notification for new messages
  useEffect(() => {
    if (chatMessages.length > 0) {
      const latestMessage = chatMessages[chatMessages.length - 1];
      
      // Only notify for messages we haven't seen before
      if (latestMessage.message_id && latestMessage.message_id !== lastMessageIdRef.current) {
        lastMessageIdRef.current = latestMessage.message_id;
        
        // Only show notification for received messages, not sent
        if (latestMessage.from_user_id !== latestMessage.to_user_id) {
          showMessage(
            `New message from ${latestMessage.from_user_id}`,
            latestMessage.content || latestMessage.message?.content || 'New message received',
            {
              label: 'Reply',
              onClick: () => router.push(`/chat/${latestMessage.from_user_id}`)
            }
          );
        }
      }
    }
  }, [chatMessages, showMessage, router]);

  // Show notification when connection status changes
  useEffect(() => {
    if (connectionStatus.connected) {
      showSuccess('Connected', 'Real-time connection established');
    } else if (connectionStatus.reconnectAttempts > 0) {
      showError(
        'Connection Lost',
        `Reconnecting... (attempt ${connectionStatus.reconnectAttempts})`
      );
    }
  }, [connectionStatus.connected, connectionStatus.reconnectAttempts, showSuccess, showError]);

  // Show notification for new users coming online
  useEffect(() => {
    const currentOnlineCount = onlineUsers.length;
    
    if (currentOnlineCount > lastOnlineCountRef.current && lastOnlineCountRef.current > 0) {
      const newUsers = currentOnlineCount - lastOnlineCountRef.current;
      if (newUsers === 1 && onlineUsers.length > 0) {
        const newestUser = onlineUsers[onlineUsers.length - 1];
        showSuccess(
          'User Online',
          `${newestUser.user_id} is now online`,
        );
      }
    }
    
    lastOnlineCountRef.current = currentOnlineCount;
  }, [onlineUsers, showSuccess]);

  // Show notification for network status changes
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const handleOnline = () => {
      showSuccess('Back Online', 'Internet connection restored');
    };

    const handleOffline = () => {
      showError('You are Offline', 'Check your internet connection');
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [showSuccess, showError]);
}
