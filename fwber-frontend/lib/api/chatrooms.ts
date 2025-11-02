import { apiClient } from './client';

export interface Chatroom {
  id: number;
  name: string;
  description?: string;
  type: 'interest' | 'city' | 'event' | 'private';
  category?: string;
  city?: string;
  neighborhood?: string;
  created_by: number;
  is_public: boolean;
  is_active: boolean;
  member_count: number;
  message_count: number;
  last_activity_at: string;
  settings?: Record<string, any>;
  creator?: {
    id: number;
    name: string;
    email: string;
  };
  recent_messages?: ChatroomMessage[];
  url: string;
  display_name: string;
}

export interface ChatroomMessage {
  id: number;
  chatroom_id: number;
  user_id: number;
  parent_id?: number;
  content: string;
  message_type: 'text' | 'image' | 'file' | 'announcement';
  metadata?: Record<string, any>;
  is_edited: boolean;
  is_deleted: boolean;
  is_pinned: boolean;
  is_announcement: boolean;
  reaction_count: number;
  reply_count: number;
  created_at: string;
  updated_at: string;
  edited_at?: string;
  deleted_at?: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
  reactions?: ChatroomMessageReaction[];
  mentions?: ChatroomMessageMention[];
  parent?: ChatroomMessage;
  display_content: string;
  display_user: string;
  preview: string;
  reaction_summary: Record<string, number>;
}

export interface ChatroomMessageReaction {
  id: number;
  message_id: number;
  user_id: number;
  emoji: string;
  created_at: string;
  user?: {
    id: number;
    name: string;
  };
}

export interface ChatroomMessageMention {
  id: number;
  message_id: number;
  mentioned_user_id: number;
  position: number;
  length: number;
  mentioned_user?: {
    id: number;
    name: string;
  };
}

export interface ChatroomMember {
  id: number;
  name: string;
  email: string;
  pivot: {
    role: 'admin' | 'moderator' | 'member';
    is_muted: boolean;
    is_banned: boolean;
    joined_at: string;
    last_seen_at: string;
    preferences?: Record<string, any>;
  };
}

export interface CreateChatroomRequest {
  name: string;
  description?: string;
  type: 'interest' | 'city' | 'event' | 'private';
  category?: string;
  city?: string;
  neighborhood?: string;
  is_public?: boolean;
  settings?: Record<string, any>;
}

export interface SendMessageRequest {
  content: string;
  message_type?: 'text' | 'image' | 'file' | 'announcement';
  parent_id?: number;
  metadata?: Record<string, any>;
}

export interface AddReactionRequest {
  emoji: string;
}

export interface ChatroomFilters {
  type?: 'interest' | 'city' | 'event' | 'private';
  category?: string;
  city?: string;
  search?: string;
  sort?: 'newest' | 'most_active' | 'most_members';
}

export interface MessageFilters {
  type?: 'text' | 'image' | 'file' | 'announcement';
  user_id?: number;
  pinned?: boolean;
  announcements?: boolean;
}

export interface ChatroomResponse {
  data: Chatroom[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface MessageResponse {
  data: ChatroomMessage[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface MemberResponse {
  data: ChatroomMember[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

/**
 * Get all available chatrooms with filtering
 */
export async function getChatrooms(filters: ChatroomFilters = {}): Promise<ChatroomResponse> {
  const params = new URLSearchParams();
  
  if (filters.type) params.append('type', filters.type);
  if (filters.category) params.append('category', filters.category);
  if (filters.city) params.append('city', filters.city);
  if (filters.search) params.append('search', filters.search);
  if (filters.sort) params.append('sort', filters.sort);

  const response = await apiClient.get(`/chatrooms?${params.toString()}`);
  return response.data;
}

/**
 * Get a specific chatroom with messages
 */
export async function getChatroom(id: number): Promise<{
  chatroom: Chatroom;
  messages: MessageResponse;
}> {
  const response = await apiClient.get(`/chatrooms/${id}`);
  return response.data;
}

/**
 * Create a new chatroom
 */
export async function createChatroom(data: CreateChatroomRequest): Promise<Chatroom> {
  const response = await apiClient.post('/chatrooms', data);
  return response.data;
}

/**
 * Join a chatroom
 */
export async function joinChatroom(id: number): Promise<{ message: string }> {
  const response = await apiClient.post(`/chatrooms/${id}/join`);
  return response.data;
}

/**
 * Leave a chatroom
 */
export async function leaveChatroom(id: number): Promise<{ message: string }> {
  const response = await apiClient.post(`/chatrooms/${id}/leave`);
  return response.data;
}

/**
 * Get chatroom members
 */
export async function getChatroomMembers(id: number): Promise<MemberResponse> {
  const response = await apiClient.get(`/chatrooms/${id}/members`);
  return response.data;
}

/**
 * Update chatroom settings (admin/moderator only)
 */
export async function updateChatroom(id: number, data: Partial<CreateChatroomRequest>): Promise<Chatroom> {
  const response = await apiClient.put(`/chatrooms/${id}`, data);
  return response.data;
}

/**
 * Delete chatroom (creator only)
 */
export async function deleteChatroom(id: number): Promise<{ message: string }> {
  const response = await apiClient.delete(`/chatrooms/${id}`);
  return response.data;
}

/**
 * Get user's chatrooms
 */
export async function getMyChatrooms(): Promise<Chatroom[]> {
  const response = await apiClient.get('/chatrooms/my');
  return response.data;
}

/**
 * Get chatroom categories
 */
export async function getChatroomCategories(): Promise<string[]> {
  const response = await apiClient.get('/chatrooms/categories');
  return response.data;
}

/**
 * Get popular chatrooms
 */
export async function getPopularChatrooms(): Promise<Chatroom[]> {
  const response = await apiClient.get('/chatrooms/popular');
  return response.data;
}

/**
 * Search chatrooms
 */
export async function searchChatrooms(query: string, filters: Partial<ChatroomFilters> = {}): Promise<ChatroomResponse> {
  const params = new URLSearchParams({ q: query });
  
  if (filters.type) params.append('type', filters.type);
  if (filters.category) params.append('category', filters.category);
  if (filters.city) params.append('city', filters.city);

  const response = await apiClient.get(`/chatrooms/search?${params.toString()}`);
  return response.data;
}

/**
 * Get messages for a chatroom
 */
export async function getChatroomMessages(
  chatroomId: number,
  filters: MessageFilters = {}
): Promise<MessageResponse> {
  const params = new URLSearchParams();
  
  if (filters.type) params.append('type', filters.type);
  if (filters.user_id) params.append('user_id', filters.user_id.toString());
  if (filters.pinned !== undefined) params.append('pinned', filters.pinned.toString());
  if (filters.announcements !== undefined) params.append('announcements', filters.announcements.toString());

  const response = await apiClient.get(`/chatrooms/${chatroomId}/messages?${params.toString()}`);
  return response.data;
}

/**
 * Send a message to a chatroom
 */
export async function sendMessage(chatroomId: number, data: SendMessageRequest): Promise<ChatroomMessage> {
  const response = await apiClient.post(`/chatrooms/${chatroomId}/messages`, data);
  return response.data;
}

/**
 * Get a specific message
 */
export async function getMessage(chatroomId: number, messageId: number): Promise<ChatroomMessage> {
  const response = await apiClient.get(`/chatrooms/${chatroomId}/messages/${messageId}`);
  return response.data;
}

/**
 * Edit a message
 */
export async function editMessage(
  chatroomId: number,
  messageId: number,
  content: string
): Promise<ChatroomMessage> {
  const response = await apiClient.put(`/chatrooms/${chatroomId}/messages/${messageId}`, { content });
  return response.data;
}

/**
 * Delete a message
 */
export async function deleteMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.delete(`/chatrooms/${chatroomId}/messages/${messageId}`);
  return response.data;
}

/**
 * Add reaction to a message
 */
export async function addReaction(
  chatroomId: number,
  messageId: number,
  data: AddReactionRequest
): Promise<{ message: string }> {
  const response = await apiClient.post(`/chatrooms/${chatroomId}/messages/${messageId}/reactions`, data);
  return response.data;
}

/**
 * Remove reaction from a message
 */
export async function removeReaction(
  chatroomId: number,
  messageId: number,
  data: AddReactionRequest
): Promise<{ message: string }> {
  const response = await apiClient.delete(`/chatrooms/${chatroomId}/messages/${messageId}/reactions`, { data });
  return response.data;
}

/**
 * Pin a message (moderator only)
 */
export async function pinMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.post(`/chatrooms/${chatroomId}/messages/${messageId}/pin`);
  return response.data;
}

/**
 * Unpin a message (moderator only)
 */
export async function unpinMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.delete(`/chatrooms/${chatroomId}/messages/${messageId}/pin`);
  return response.data;
}

/**
 * Get pinned messages for a chatroom
 */
export async function getPinnedMessages(chatroomId: number): Promise<ChatroomMessage[]> {
  const response = await apiClient.get(`/chatrooms/${chatroomId}/messages/pinned`);
  return response.data;
}

/**
 * Get message replies
 */
export async function getMessageReplies(chatroomId: number, messageId: number): Promise<ChatroomMessage[]> {
  const response = await apiClient.get(`/chatrooms/${chatroomId}/messages/${messageId}/replies`);
  return response.data;
}
