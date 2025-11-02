import { useEffect, useRef, useState } from 'react';
import { useAuth } from '@/lib/auth-context';

interface MercureSSEOptions {
  topics: string[];
  onMessage?: (data: any) => void;
  onError?: (error: Event) => void;
  onOpen?: () => void;
  onClose?: () => void;
  autoReconnect?: boolean;
  reconnectInterval?: number;
}

export const useMercureSSE = (options: MercureSSEOptions) => {
  const { token } = useAuth();
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const eventSourceRef = useRef<EventSource | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const {
    topics,
    onMessage,
    onError,
    onOpen,
    onClose,
    autoReconnect = true,
    reconnectInterval = 5000
  } = options;

  const connect = async () => {
    if (!token) {
      setError('No authentication token available');
      return;
    }

    try {
      // First, get the Mercure authorization cookie
      const cookieResponse = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/mercure/cookie`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Important for cookies
      });

      if (!cookieResponse.ok) {
        throw new Error('Failed to get Mercure authorization');
      }

      // Build the Mercure URL with topics
      const mercureUrl = new URL(`${process.env.NEXT_PUBLIC_MERCURE_URL}/.well-known/mercure`);
      topics.forEach(topic => {
        mercureUrl.searchParams.append('topic', topic);
      });

      // Create EventSource connection
      const eventSource = new EventSource(mercureUrl.toString(), {
        withCredentials: true // Include cookies for authorization
      });

      eventSource.onopen = () => {
        setIsConnected(true);
        setError(null);
        onOpen?.();
      };

      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          onMessage?.(data);
        } catch (e) {
          console.error('Failed to parse SSE message:', e);
        }
      };

      eventSource.onerror = (error) => {
        setIsConnected(false);
        setError('Connection error');
        onError?.(error);
        
        if (autoReconnect) {
          reconnectTimeoutRef.current = setTimeout(() => {
            connect();
          }, reconnectInterval);
        }
      };

      eventSourceRef.current = eventSource;

    } catch (err) {
      setError(err instanceof Error ? err.message : 'Connection failed');
      setIsConnected(false);
    }
  };

  const disconnect = () => {
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
    
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
      reconnectTimeoutRef.current = null;
    }
    
    setIsConnected(false);
    onClose?.();
  };

  useEffect(() => {
    if (token && topics.length > 0) {
      connect();
    }

    return () => {
      disconnect();
    };
  }, [token, topics.join(',')]);

  return {
    isConnected,
    error,
    connect,
    disconnect
  };
};

// Specialized hook for bulletin board messages
export const useBulletinBoardMercure = (boardId: number) => {
  const [messages, setMessages] = useState<any[]>([]);
  const [boardActivity, setBoardActivity] = useState<any>(null);

  const { isConnected, error } = useMercureSSE({
    topics: [
      `https://fwber.me/bulletin-boards/${boardId}`,
      'https://fwber.me/public/bulletin-boards'
    ],
    onMessage: (data) => {
      if (data.type === 'new_message') {
        setMessages(prev => [...prev, data.data]);
      } else if (data.type === 'board_activity' && data.board_id === boardId) {
        setBoardActivity(data);
      }
    },
    onError: (error) => {
      console.error('Bulletin board SSE error:', error);
    }
  });

  return {
    isConnected,
    error,
    messages,
    boardActivity
  };
};
