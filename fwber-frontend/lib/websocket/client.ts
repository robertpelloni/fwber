import React from 'react';
import { apiClient } from '../api/client';

export interface WebSocketMessage {
  type: string;
  data: any;
  timestamp: string;
  connection_id?: string;
  user_id?: string;
}

export interface WebSocketConnection {
  connectionId: string;
  userId: string;
  channels: string[];
  heartbeatInterval: number;
  connectedAt: string;
}

export interface PresenceUpdate {
  user_id: string;
  status: 'online' | 'away' | 'busy' | 'offline';
  metadata?: Record<string, any>;
  timestamp: string;
}

export interface ChatMessage {
  from_user_id: string;
  to_user_id: string;
  message: {
    id: string;
    type: string;
    content: string;
    timestamp: string;
  };
}

export interface TypingIndicator {
  from_user_id: string;
  to_user_id: string;
  is_typing: boolean;
  timestamp: string;
}

export interface Notification {
  user_id: string;
  notification: {
    title: string;
    body: string;
    type?: string;
    data?: Record<string, any>;
  };
  timestamp: string;
}

export interface OnlineUser {
  user_id: string;
  status: string;
  last_seen: string;
  metadata: Record<string, any>;
}

export interface UserConnection {
  connection_id: string;
  connected_at: string;
  last_activity: string;
  metadata: Record<string, any>;
}

export class WebSocketClient {
  private ws: WebSocket | null = null;
  private connectionId: string | null = null;
  private userId: string | null = null;
  private heartbeatInterval: number = 30000; // 30 seconds
  private heartbeatTimer: NodeJS.Timeout | null = null;
  private reconnectAttempts: number = 0;
  private maxReconnectAttempts: number = 5;
  private reconnectDelay: number = 1000; // 1 second
  private isConnecting: boolean = false;
  private messageQueue: WebSocketMessage[] = [];
  private eventListeners: Map<string, Function[]> = new Map();

  constructor(
    private wsUrl: string,
    private token: string,
    private options: {
      autoConnect?: boolean;
      heartbeatInterval?: number;
      maxReconnectAttempts?: number;
      reconnectDelay?: number;
    } = {}
  ) {
    this.heartbeatInterval = options.heartbeatInterval || 30000;
    this.maxReconnectAttempts = options.maxReconnectAttempts || 5;
    this.reconnectDelay = options.reconnectDelay || 1000;

    if (options.autoConnect !== false) {
      this.connect();
    }
  }

  /**
   * Connect to WebSocket server
   */
  public async connect(): Promise<void> {
    if (this.isConnecting || (this.ws && this.ws.readyState === WebSocket.OPEN)) {
      return;
    }

    this.isConnecting = true;

    try {
      // First, establish connection via HTTP API
      const connectionData = await this.establishConnection();
      this.connectionId = connectionData.connection_id;
      this.userId = connectionData.user_id;
      this.heartbeatInterval = connectionData.heartbeat_interval * 1000;

      // Then connect WebSocket
      const wsUrl = `${this.wsUrl}?token=${this.token}&connection_id=${this.connectionId}`;
      this.ws = new WebSocket(wsUrl);

      this.ws.onopen = this.handleOpen.bind(this);
      this.ws.onmessage = this.handleMessage.bind(this);
      this.ws.onclose = this.handleClose.bind(this);
      this.ws.onerror = this.handleError.bind(this);

    } catch (error) {
      console.error('WebSocket connection failed:', error);
      this.isConnecting = false;
      this.handleReconnect();
    }
  }

  /**
   * Disconnect from WebSocket server
   */
  public disconnect(): void {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }

    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }

    this.connectionId = null;
    this.userId = null;
    this.reconnectAttempts = 0;
  }

  /**
   * Send message to WebSocket server
   */
  public send(message: WebSocketMessage): void {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(message));
    } else {
      // Queue message if not connected
      this.messageQueue.push(message);
    }
  }

  /**
   * Send chat message
   */
  public sendChatMessage(recipientId: string, content: string, type: string = 'text'): void {
    const message: WebSocketMessage = {
      type: 'chat_message',
      data: {
        to_user_id: recipientId,
        message: {
          id: this.generateMessageId(),
          type,
          content,
          timestamp: new Date().toISOString(),
        },
      },
      timestamp: new Date().toISOString(),
    };

    this.send(message);
  }

  /**
   * Send typing indicator
   */
  public sendTypingIndicator(recipientId: string, isTyping: boolean): void {
    const message: WebSocketMessage = {
      type: 'typing_indicator',
      data: {
        to_user_id: recipientId,
        is_typing: isTyping,
      },
      timestamp: new Date().toISOString(),
    };

    this.send(message);
  }

  /**
   * Update presence status
   */
  public updatePresence(status: 'online' | 'away' | 'busy' | 'offline', metadata?: Record<string, any>): void {
    const message: WebSocketMessage = {
      type: 'presence_update',
      data: {
        status,
        metadata,
      },
      timestamp: new Date().toISOString(),
    };

    this.send(message);
  }

  /**
   * Send notification
   */
  public sendNotification(recipientId: string, notification: {
    title: string;
    body: string;
    type?: string;
    data?: Record<string, any>;
  }): void {
    const message: WebSocketMessage = {
      type: 'notification',
      data: {
        recipient_id: recipientId,
        notification,
      },
      timestamp: new Date().toISOString(),
    };

    this.send(message);
  }

  /**
   * Add event listener
   */
  public on(event: string, callback: Function): void {
    if (!this.eventListeners.has(event)) {
      this.eventListeners.set(event, []);
    }
    this.eventListeners.get(event)!.push(callback);
  }

  /**
   * Remove event listener
   */
  public off(event: string, callback: Function): void {
    const listeners = this.eventListeners.get(event);
    if (listeners) {
      const index = listeners.indexOf(callback);
      if (index > -1) {
        listeners.splice(index, 1);
      }
    }
  }

  /**
   * Emit event to listeners
   */
  private emit(event: string, data: any): void {
    const listeners = this.eventListeners.get(event);
    if (listeners) {
      listeners.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error('Event listener error:', error);
        }
      });
    }
  }

  /**
   * Get connection status
   */
  public getConnectionStatus(): {
    connected: boolean;
    connectionId: string | null;
    userId: string | null;
    reconnectAttempts: number;
  } {
    return {
      connected: this.ws?.readyState === WebSocket.OPEN,
      connectionId: this.connectionId,
      userId: this.userId,
      reconnectAttempts: this.reconnectAttempts,
    };
  }

  /**
   * Handle WebSocket open
   */
  private handleOpen(): void {
    console.log('WebSocket connected');
    this.isConnecting = false;
    this.reconnectAttempts = 0;
    
    // Start heartbeat
    this.startHeartbeat();
    
    // Send queued messages
    this.sendQueuedMessages();
    
    // Emit connection event
    this.emit('connection', {
      connectionId: this.connectionId,
      userId: this.userId,
    });
  }

  /**
   * Handle WebSocket message
   */
  private handleMessage(event: MessageEvent): void {
    try {
      const message: WebSocketMessage = JSON.parse(event.data);
      
      // Emit message event
      this.emit('message', message);
      
      // Emit specific message type events
      this.emit(message.type, message.data);
      
    } catch (error) {
      console.error('Failed to parse WebSocket message:', error);
    }
  }

  /**
   * Handle WebSocket close
   */
  private handleClose(event: CloseEvent): void {
    console.log('WebSocket disconnected:', event.code, event.reason);
    
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
      this.heartbeatTimer = null;
    }
    
    this.isConnecting = false;
    
    // Emit disconnection event
    this.emit('disconnection', {
      code: event.code,
      reason: event.reason,
    });
    
    // Attempt to reconnect
    this.handleReconnect();
  }

  /**
   * Handle WebSocket error
   */
  private handleError(error: Event): void {
    console.error('WebSocket error:', error);
    this.emit('error', error);
  }

  /**
   * Handle reconnection
   */
  private handleReconnect(): void {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.log('Max reconnection attempts reached');
      this.emit('max_reconnect_attempts', { attempts: this.reconnectAttempts });
      return;
    }

    this.reconnectAttempts++;
    const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
    
    console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
    
    setTimeout(() => {
      this.connect();
    }, delay);
  }

  /**
   * Start heartbeat
   */
  private startHeartbeat(): void {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer);
    }

    this.heartbeatTimer = setInterval(() => {
      if (this.ws && this.ws.readyState === WebSocket.OPEN) {
        this.send({
          type: 'heartbeat',
          data: { timestamp: new Date().toISOString() },
          timestamp: new Date().toISOString(),
        });
      }
    }, this.heartbeatInterval);
  }

  /**
   * Send queued messages
   */
  private sendQueuedMessages(): void {
    while (this.messageQueue.length > 0) {
      const message = this.messageQueue.shift();
      if (message) {
        this.send(message);
      }
    }
  }

  /**
   * Establish connection via HTTP API
   */
  private async establishConnection(): Promise<{
    connection_id: string;
    user_id: string;
    channels: string[];
    heartbeat_interval: number;
  }> {
    const response = await apiClient.post<{
      connection_id: string;
      user_id: string;
      channels: string[];
      heartbeat_interval: number;
    }>('/websocket/connect', {
      connection_data: {
        user_agent: navigator.userAgent,
        ip_address: '', // Will be determined by server
        device_type: this.getDeviceType(),
      },
    });

    return response.data;
  }

  /**
   * Get device type
   */
  private getDeviceType(): string {
    const userAgent = navigator.userAgent;
    if (/Mobile|Android|iPhone|iPad/.test(userAgent)) {
      return 'mobile';
    } else if (/Tablet|iPad/.test(userAgent)) {
      return 'tablet';
    } else {
      return 'desktop';
    }
  }

  /**
   * Generate message ID
   */
  private generateMessageId(): string {
    return `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

/**
 * Create WebSocket client instance
 */
export function createWebSocketClient(
  wsUrl: string,
  token: string,
  options?: {
    autoConnect?: boolean;
    heartbeatInterval?: number;
    maxReconnectAttempts?: number;
    reconnectDelay?: number;
  }
): WebSocketClient {
  return new WebSocketClient(wsUrl, token, options);
}

/**
 * WebSocket hook for React components
 */
export function useWebSocket(
  wsUrl: string,
  token: string,
  options?: {
    autoConnect?: boolean;
    heartbeatInterval?: number;
    maxReconnectAttempts?: number;
    reconnectDelay?: number;
  }
) {
  const [client, setClient] = React.useState<WebSocketClient | null>(null);
  const [connectionStatus, setConnectionStatus] = React.useState<{
    connected: boolean;
    connectionId: string | null;
    userId: string | null;
    reconnectAttempts: number;
  }>({
    connected: false,
    connectionId: null,
    userId: null,
    reconnectAttempts: 0,
  });

  // Extract specific option values for stable dependencies
  const autoConnect = options?.autoConnect;
  const heartbeatInterval = options?.heartbeatInterval;
  const maxReconnectAttempts = options?.maxReconnectAttempts;
  const reconnectDelay = options?.reconnectDelay;

  React.useEffect(() => {
    const wsClient = new WebSocketClient(wsUrl, token, options);
    setClient(wsClient);

    // Listen for connection status changes
    const handleConnection = () => {
      setConnectionStatus(wsClient.getConnectionStatus());
    };

    const handleDisconnection = () => {
      setConnectionStatus(wsClient.getConnectionStatus());
    };

    wsClient.on('connection', handleConnection);
    wsClient.on('disconnection', handleDisconnection);

    return () => {
      wsClient.off('connection', handleConnection);
      wsClient.off('disconnection', handleDisconnection);
      wsClient.disconnect();
    };
  }, [wsUrl, token, autoConnect, heartbeatInterval, maxReconnectAttempts, reconnectDelay, options]);

  return {
    client,
    connectionStatus,
    connect: () => client?.connect(),
    disconnect: () => client?.disconnect(),
  };
}
