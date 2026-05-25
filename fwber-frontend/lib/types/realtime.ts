/**
 * Shared types for WebSocket/Pusher real-time communication.
 * 
 * Both `use-websocket-logic.ts` (raw WebSocket) and `use-pusher-logic.ts` (Laravel Echo/Pusher)
 * use these types. They were previously duplicated across both files.
 * Consolidated here as the single source of truth.
 */

export interface ConnectionStatus {
    connected: boolean;
    connecting?: boolean;
    connectionId?: string | null;
    userId?: string | null;
    reconnectAttempts?: number;
    error?: Error | null;
}

export interface RealtimeMessage {
    id?: string;
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
    type?: string;
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
    reactions?: Array<{ emoji: string; user_id: string; user_name: string }>;
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
    // Allow dynamic properties from various notification types (gift_name, sender_name, etc.)
    [key: string]: any;
}

export interface UseWebSocketOptions {
    autoConnect?: boolean;
    heartbeatInterval?: number;
    maxReconnectAttempts?: number;
    reconnectDelay?: number;
    wsUrl?: string;
}
