/**
 * Messages API Client
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 3C - Messaging System Integration
 * Purpose: Type-safe API client for messaging operations
 * 
 * Created: 2025-01-19
 * Updated: 2025-10-27 (Refactored to use shared apiClient)
 */

import { api } from './client';

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
  const data = await api.get<any>('/matches/established', {
    headers: { Authorization: `Bearer ${token}` }
  });
  return data.data || data;
}

/**
 * Fetch messages for a specific conversation (user)
 */
export async function getMessages(token: string, userId: number): Promise<Message[]> {
  const data = await api.get<any>(`/messages/${userId}`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return data.messages || data.data || data;
}

/**
 * Send a message to a user (supports text and media)
 */
export async function sendMessage(
  token: string, 
  receiverId: number, 
  content: string,
  media?: File | null,
  messageType?: 'text' | 'image' | 'video' | 'audio' | 'file'
): Promise<Message> {
  const formData = new FormData();
  formData.append('receiver_id', receiverId.toString());
  formData.append('content', content);
  
  if (media) {
    formData.append('media', media);
  }

  if (messageType) {
    formData.append('message_type', messageType);
  } else if (media) {
     // Auto-detect simple types if not provided
     if (media.type.startsWith('image/')) formData.append('message_type', 'image');
     else if (media.type.startsWith('video/')) formData.append('message_type', 'video');
     else if (media.type.startsWith('audio/')) formData.append('message_type', 'audio');
     else formData.append('message_type', 'file');
  } else {
    formData.append('message_type', 'text');
  }

  const data = await api.post<any>('/messages', formData, {
    headers: { Authorization: `Bearer ${token}` }
  });
  
  return data.message || data.data || data;
}

/**
 * Mark messages as read in a conversation
 */
export async function markMessagesAsRead(
  token: string, 
  userId: number
): Promise<void> {
  await api.post(`/messages/mark-all-read/${userId}`, {}, {
    headers: { Authorization: `Bearer ${token}` }
  });
}

/**
 * Get unread message count for the authenticated user
 */
export async function getUnreadCount(token: string): Promise<number> {
  const data = await api.get<any>('/messages/unread-count', {
    headers: { Authorization: `Bearer ${token}` }
  });
  return data.unread_count || data.count || 0;
}
