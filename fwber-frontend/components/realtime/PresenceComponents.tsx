'use client';

import { useEffect, useMemo, useContext, createContext, useState } from 'react';

// Create a fallback context for when WebSocketProvider is not available
const FallbackWebSocketContext = createContext<WebSocketContextValue | null>(null);

// Type definitions for WebSocket context - exported for bridge usage
export interface OnlineUser {
  user_id: string;
  status: string;
}

export interface TypingIndicatorData {
  from_user_id: string;
  is_typing: boolean;
}

export interface WebSocketContextValue {
  onlineUsers?: OnlineUser[];
  typingIndicators?: TypingIndicatorData[];
  connectionStatus: {
    connected: boolean;
    reconnectAttempts: number;
  };
  sendTypingIndicator?: (recipientId: string, isTyping: boolean) => void;
}

// Default context value when not in provider
const defaultContextValue: WebSocketContextValue = {
  onlineUsers: [],
  typingIndicators: [],
  connectionStatus: {
    connected: false,
    reconnectAttempts: 0,
  },
};

// Export a provider wrapper that can be used to inject context
export function PresenceProvider({ 
  children, 
  value 
}: { 
  children: React.ReactNode; 
  value?: WebSocketContextValue;
}) {
  return (
    <FallbackWebSocketContext.Provider value={value || defaultContextValue}>
      {children}
    </FallbackWebSocketContext.Provider>
  );
}

// Hook to use the presence context
export function usePresenceContext(): WebSocketContextValue {
  const context = useContext(FallbackWebSocketContext);
  return context || defaultContextValue;
}

interface PresenceIndicatorProps {
  userId: string;
  showLabel?: boolean;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

/**
 * Displays real-time online/offline status for a user
 */
export function PresenceIndicator({
  userId,
  showLabel = false,
  size = 'md',
  className = '',
}: PresenceIndicatorProps) {
  const context = usePresenceContext();

  const status = useMemo(() => {
    if (!context?.onlineUsers) return 'offline';
    const user = context.onlineUsers.find((u) => u.user_id === userId);
    return user?.status || 'offline';
  }, [context, userId]);

  const sizeClasses = {
    sm: 'w-2 h-2',
    md: 'w-3 h-3',
    lg: 'w-4 h-4',
  };

  const statusColors = {
    online: 'bg-green-500',
    away: 'bg-yellow-500',
    busy: 'bg-red-500',
    offline: 'bg-gray-400',
  };

  const statusLabels = {
    online: 'Online',
    away: 'Away',
    busy: 'Busy',
    offline: 'Offline',
  };

  return (
    <div className={`inline-flex items-center gap-1.5 ${className}`}>
      <span
        className={`${sizeClasses[size]} ${statusColors[status as keyof typeof statusColors]} rounded-full ring-2 ring-white`}
        title={statusLabels[status as keyof typeof statusLabels]}
      />
      {showLabel && (
        <span className="text-xs text-gray-500 capitalize">{status}</span>
      )}
    </div>
  );
}

interface TypingIndicatorProps {
  /** User ID or chatroom ID to show typing for */
  contextId: string;
  /** Type of context - 'user' for DMs or 'chatroom' */
  contextType: 'user' | 'chatroom' | 'proximity_chatroom';
  className?: string;
}

/**
 * Displays real-time typing indicator
 */
export function TypingIndicator({
  contextId,
  contextType,
  className = '',
}: TypingIndicatorProps) {
  const context = usePresenceContext();

  const typingUsers = useMemo(() => {
    if (!context?.typingIndicators) return [];

    return context.typingIndicators
      .filter((t: TypingIndicatorData) => {
        if (contextType === 'user') {
          return t.from_user_id === contextId && t.is_typing;
        }
        // For chatrooms, we'd need chatroom_id in the typing indicator
        // Currently the TypingIndicatorData interface doesn't support chatroom_id
        // This is a placeholder for future implementation
        return false;
      })
      .map((t: TypingIndicatorData) => t.from_user_id);
  }, [context, contextId, contextType]);

  if (typingUsers.length === 0) return null;

  return (
    <div className={`flex items-center gap-2 text-sm text-gray-500 ${className}`}>
      <div className="flex space-x-1">
        <span className="typing-dot typing-dot-1 w-2 h-2 bg-gray-400 rounded-full animate-bounce" />
        <span className="typing-dot typing-dot-2 w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:150ms]" />
        <span className="typing-dot typing-dot-3 w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:300ms]" />
      </div>
      <span>
        {typingUsers.length === 1 ? 'Someone is typing...' : `${typingUsers.length} people are typing...`}
      </span>
    </div>
  );
}

interface OnlineUsersListProps {
  userIds?: string[];
  maxDisplay?: number;
  showCount?: boolean;
  className?: string;
}

/**
 * Displays a list of online users with their status
 */
export function OnlineUsersList({
  userIds,
  maxDisplay = 10,
  showCount = true,
  className = '',
}: OnlineUsersListProps) {
  const context = usePresenceContext();

  const onlineUsers = useMemo(() => {
    if (!context?.onlineUsers) return [];

    let users = context.onlineUsers;
    if (userIds) {
      users = users.filter((u: OnlineUser) => userIds.includes(u.user_id));
    }
    return users.filter((u: OnlineUser) => u.status !== 'offline');
  }, [context, userIds]);

  const displayUsers = onlineUsers.slice(0, maxDisplay);
  const remainingCount = onlineUsers.length - maxDisplay;

  if (onlineUsers.length === 0) {
    return (
      <div className={`text-sm text-gray-500 ${className}`}>
        No one is online
      </div>
    );
  }

  return (
    <div className={className}>
      {showCount && (
        <div className="text-sm font-medium text-gray-700 mb-2">
          {onlineUsers.length} online
        </div>
      )}
      <div className="flex flex-wrap gap-2">
        {displayUsers.map((user: OnlineUser) => (
          <div
            key={user.user_id}
            className="inline-flex items-center gap-1.5 px-2 py-1 bg-gray-100 rounded-full text-sm"
          >
            <PresenceIndicator userId={user.user_id} size="sm" />
            <span className="text-gray-700">User #{user.user_id}</span>
          </div>
        ))}
        {remainingCount > 0 && (
          <div className="inline-flex items-center px-2 py-1 bg-gray-100 rounded-full text-sm text-gray-500">
            +{remainingCount} more
          </div>
        )}
      </div>
    </div>
  );
}

interface ConnectionStatusBadgeProps {
  showReconnecting?: boolean;
  className?: string;
}

/**
 * Shows WebSocket connection status
 */
export function ConnectionStatusBadge({
  showReconnecting = true,
  className = '',
}: ConnectionStatusBadgeProps) {
  const context = usePresenceContext();
  const [isOnline, setIsOnline] = useState(true);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      setIsOnline(navigator.onLine);

      const handleOnline = () => setIsOnline(true);
      const handleOffline = () => setIsOnline(false);

      window.addEventListener('online', handleOnline);
      window.addEventListener('offline', handleOffline);

      return () => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
      };
    }
  }, []);

  const { connected, reconnectAttempts } = context.connectionStatus;
  const isReconnecting = !connected && reconnectAttempts > 0;

  if (!isOnline) {
    return (
      <div className={`inline-flex items-center gap-1.5 text-xs ${className}`}>
        <span className="w-2 h-2 bg-red-500 rounded-full" />
        <span className="text-red-600 font-medium">Offline</span>
      </div>
    );
  }

  if (connected) {
    return (
      <div className={`inline-flex items-center gap-1.5 text-xs ${className}`}>
        <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
        <span className="text-green-600">Connected</span>
      </div>
    );
  }

  if (showReconnecting && isReconnecting) {
    return (
      <div className={`inline-flex items-center gap-1.5 text-xs ${className}`}>
        <span className="w-2 h-2 bg-yellow-500 rounded-full animate-pulse" />
        <span className="text-yellow-600">
          Reconnecting... ({reconnectAttempts})
        </span>
      </div>
    );
  }

  return (
    <div className={`inline-flex items-center gap-1.5 text-xs ${className}`}>
      <span className="w-2 h-2 bg-gray-400 rounded-full" />
      <span className="text-gray-500">Disconnected</span>
    </div>
  );
}

interface UseTypingNotifierOptions {
  recipientId: string;
}

/**
 * Hook to manage sending typing indicators
 */
export function useTypingNotifier({ recipientId }: UseTypingNotifierOptions) {
  const context = usePresenceContext();

  useEffect(() => {
    return () => {
      // Send stop typing when component unmounts
      if (context?.sendTypingIndicator) {
        context.sendTypingIndicator(recipientId, false);
      }
    };
  }, [context, recipientId]);

  const sendTyping = () => {
    if (context?.sendTypingIndicator) {
      context.sendTypingIndicator(recipientId, true);
    }
  };

  const stopTyping = () => {
    if (context?.sendTypingIndicator) {
      context.sendTypingIndicator(recipientId, false);
    }
  };

  return { sendTyping, stopTyping };
}
