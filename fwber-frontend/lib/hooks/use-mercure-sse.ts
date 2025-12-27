import { useEffect, useState } from 'react';
import { usePusherLogic } from './use-pusher-logic';

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
  // Shim: Do nothing, or maybe use Pusher if topics can be mapped
  console.warn('useMercureSSE is deprecated. Please migrate to usePusherLogic.');
  return {
    isConnected: false,
    error: null,
    connect: () => {},
    disconnect: () => {}
  };
};

// Specialized hook for bulletin board messages
export const useBulletinBoardMercure = (boardId: number) => {
  const [messages, setMessages] = useState<any[]>([]);
  const [boardActivity, setBoardActivity] = useState<any>(null);
  const { echo } = usePusherLogic();
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
      if (!echo || !boardId) return;
      
      const channel = echo.channel(`bulletin-board.${boardId}`);
      channel.listen('.message.created', (e: any) => {
          setMessages(prev => [...prev, e.message]);
      });
      
      const publicChannel = echo.channel('bulletin-boards.public');
      publicChannel.listen('.board.activity', (e: any) => {
          if (e.boardId === boardId) {
              setBoardActivity(e);
          }
      });
      
      setIsConnected(true);
      
      return () => {
          echo.leave(`bulletin-board.${boardId}`);
          echo.leave('bulletin-boards.public');
          setIsConnected(false);
      };
  }, [echo, boardId]);

  return {
    isConnected,
    error: null,
    messages,
    boardActivity
  };
};
