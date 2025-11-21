/**
 * Messages API Client
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 3C - Messaging System Integration
 * Purpose: Type-safe API client for messaging operations
 * 
 * Created: 2025-01-19
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface Message {
  id: number;
  conversation_id: number;
  sender_id: number;
  receiver_id: number;
  content: string;
  message_type: 'text' | 'image' | 'video' | 'audio' | 'file';
  media_url?: string;
  thumbnail_url?: string;
  media_duration?: number;
  created_at: string;
  updated_at: string;
  is_read?: boolean;
  read_at?: string;
}

export interface Conversation {
  id: number;
  user1_id: number;
  user2_id: number;
  created_at: string;
  updated_at: string;
  last_message?: Message;
  other_user?: {
    id: number;
    email: string;
    profile?: {
      display_name: string | null;
      age: number | null;
      photos?: Array<{
        id: number;
        url: string;
        is_private: boolean;
        is_primary: boolean;
      }>;
    };
  };
}

export interface SendMessageRequest {
  content: string;
}

export interface SendMessageResponse {
  message: string;
  data: Message;
}

/**
 * Fetch all conversations (established matches) for the authenticated user
 */
export async function getConversations(token: string): Promise<Conversation[]> {
  const response = await fetch(`${API_BASE_URL}/matches/established`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch conversations' }));
    throw new Error(error.message || 'Failed to fetch conversations');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Fetch messages for a specific conversation (user)
 */
export async function getMessages(token: string, userId: number): Promise<Message[]> {
  const response = await fetch(`${API_BASE_URL}/messages/${userId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch messages' }));
    throw new Error(error.message || 'Failed to fetch messages');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Send a message to a user (supports text and media)
 */
export async function sendMessage(
  token: string, 
  receiverId: number, 
  content: string,
  media?: File | null
): Promise<Message> {
  const formData = new FormData();
  formData.append('receiver_id', receiverId.toString());
  formData.append('content', content);
  
  if (media) {
    formData.append('media', media);
  }

  const response = await fetch(`${API_BASE_URL}/messages`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      // 'Content-Type': 'multipart/form-data', // Let browser set this with boundary
      'Accept': 'application/json',
    },
    body: formData,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to send message' }));
    throw new Error(error.message || 'Failed to send message');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Create a new conversation with a matched user
 */
export async function createConversation(
  token: string, 
  userId: number
): Promise<Conversation> {
  const response = await fetch(`${API_BASE_URL}/conversations`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ user_id: userId }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to create conversation' }));
    throw new Error(error.message || 'Failed to create conversation');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Mark messages as read in a conversation
 */
export async function markMessagesAsRead(
  token: string, 
  conversationId: number
): Promise<void> {
  const response = await fetch(`${API_BASE_URL}/conversations/${conversationId}/read`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to mark messages as read' }));
    throw new Error(error.message || 'Failed to mark messages as read');
  }
}

/**
 * Get unread message count for the authenticated user
 */
export async function getUnreadCount(token: string): Promise<number> {
  const response = await fetch(`${API_BASE_URL}/messages/unread-count`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch unread count' }));
    throw new Error(error.message || 'Failed to fetch unread count');
  }

  const data = await response.json();
  return data.count || 0;
}
