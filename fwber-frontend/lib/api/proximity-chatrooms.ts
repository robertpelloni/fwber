import { apiClient } from './client';

export interface ProximityChatroom {
  id: number;
  name: string;
  description?: string;
  type: 'conference' | 'event' | 'venue' | 'area' | 'temporary' | 'networking' | 'social' | 'dating' | 'professional' | 'casual';
  venue_name?: string;
  venue_type?: string;
  event_name?: string;
  event_date?: string;
  event_start_time?: string;
  event_end_time?: string;
  latitude: number;
  longitude: number;
  radius_meters: number;
  geohash: string;
  city?: string;
  neighborhood?: string;
  address?: string;
  tags?: string[];
  is_networking?: boolean;
  is_social?: boolean;
  settings?: Record<string, any>;
  created_by: number;
  owner_id: number; // Added for compatibility
  is_active: boolean;
  is_public: boolean;
  requires_approval: boolean;
  max_members?: number;
  current_members: number;
  member_count: number; // Added for compatibility (same as current_members)
  message_count: number;
  last_activity_at: string;
  expires_at?: string;
  creator?: {
    id: number;
    name: string;
    email: string;
  };
  active_members?: ProximityChatroomMember[];
  distance_meters?: number;
  is_within_proximity?: boolean;
  url: string;
  display_name: string;
  token_entry_fee?: number;
}

export interface ProximityChatroomMember {
  id: number;
  user_id?: number; // For backward compatibility
  name: string;
  email: string;
  role: 'admin' | 'moderator' | 'member'; // Flattened from pivot
  user?: { // Added for compatibility
    id: number;
    name: string;
    email: string;
  };
  pivot: {
    role: 'admin' | 'moderator' | 'member';
    is_muted: boolean;
    is_banned: boolean;
    latitude?: number;
    longitude?: number;
    distance_meters?: number;
    joined_at: string;
    last_seen_at: string;
    last_location_update: string;
    preferences?: Record<string, any>;
    professional_info?: Record<string, any>;
    interests?: string[];
    is_visible: boolean;
    is_networking: boolean;
    is_social: boolean;
  };
  distance_meters?: number;
}

export interface ProximityChatroomMessage {
  id: number;
  proximity_chatroom_id: number;
  user_id: number;
  parent_id?: number;
  content: string;
  message_type: 'text' | 'image' | 'file' | 'announcement';
  metadata?: Record<string, any>;
  is_edited: boolean;
  is_deleted: boolean;
  is_pinned: boolean;
  is_announcement: boolean;
  is_networking: boolean;
  is_social: boolean;
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
  reactions?: ProximityChatroomMessageReaction[];
  mentions?: ProximityChatroomMessageMention[];
  parent?: ProximityChatroomMessage;
  display_content: string;
  display_user: string;
  preview: string;
  reaction_summary: Record<string, number>;
}

export interface ProximityChatroomMessageReaction {
  id: number;
  message_id: number;
  user_id: number;
  emoji: string;
  count?: number;
  created_at: string;
  user?: {
    id: number;
    name: string;
  };
}

export interface ProximityChatroomMessageMention {
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

export interface CreateProximityChatroomRequest {
  name: string;
  description?: string;
  type: 'conference' | 'event' | 'venue' | 'area' | 'temporary' | 'networking' | 'social' | 'dating' | 'professional' | 'casual';
  venue_name?: string;
  venue_type?: string;
  event_name?: string;
  event_date?: string;
  event_start_time?: string;
  event_end_time?: string;
  latitude: number;
  longitude: number;
  radius_meters?: number;
  city?: string;
  neighborhood?: string;
  address?: string;
  tags?: string[];
  max_members?: number;
  is_public?: boolean;
  requires_approval?: boolean;
  expires_at?: string;
}

export interface JoinProximityChatroomRequest {
  latitude: number;
  longitude: number;
  is_networking?: boolean;
  is_social?: boolean;
  professional_info?: Record<string, any>;
  interests?: string[];
}

export interface SendProximityMessageRequest {
  content: string;
  message_type?: 'text' | 'image' | 'file' | 'announcement';
  parent_id?: number;
  is_networking?: boolean;
  is_social?: boolean;
  metadata?: Record<string, any>;
}

export interface AddReactionRequest {
  emoji: string;
}

export interface FindNearbyRequest {
  latitude: number;
  longitude: number;
  radius_meters?: number;
  type?: 'conference' | 'event' | 'venue' | 'area' | 'temporary' | 'networking' | 'social' | 'dating' | 'professional' | 'casual';
  venue_type?: string;
  tags?: string[];
  search?: string;
}

export interface UpdateLocationRequest {
  latitude: number;
  longitude: number;
}

export interface NearbyNetworkingRequest {
  latitude: number;
  longitude: number;
  radius_meters?: number;
}

export interface ProximityChatroomResponse {
  data?: ProximityChatroom[]; // For paginated responses
  chatrooms: ProximityChatroom[];
  user_location: {
    latitude: number;
    longitude: number;
  };
  search_radius: number;
}

export interface NearbyNetworkingResponse {
  members: ProximityChatroomMember[];
  user_location: {
    latitude: number;
    longitude: number;
  };
  search_radius: number;
}

export interface MessageResponse {
  data: ProximityChatroomMessage[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface MemberResponse {
  data: ProximityChatroomMember[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ProximityAnalytics {
  total_members: number;
  networking_members: number;
  social_members: number;
  visible_members: number;
  total_messages: number;
  active_members: number;
  average_distance: number;
}

/**
 * Find nearby proximity chatrooms
 */
export async function findNearby(filters: FindNearbyRequest): Promise<ProximityChatroomResponse> {
  const params = new URLSearchParams();
  
  params.append('latitude', filters.latitude.toString());
  params.append('longitude', filters.longitude.toString());
  if (filters.radius_meters) params.append('radius_meters', filters.radius_meters.toString());
  if (filters.type) params.append('type', filters.type);
  if (filters.venue_type) params.append('venue_type', filters.venue_type);
  if (filters.tags) {
    filters.tags.forEach(tag => params.append('tags[]', tag));
  }
  if (filters.search) params.append('search', filters.search);

  const response = await apiClient.get<ProximityChatroomResponse>(`/proximity-chatrooms/nearby?${params.toString()}`);
  return response.data;
}

/**
 * Create a proximity chatroom
 */
export async function createProximityChatroom(data: CreateProximityChatroomRequest): Promise<ProximityChatroom> {
  const response = await apiClient.post<ProximityChatroom>('/proximity-chatrooms', data);
  return response.data;
}

/**
 * Get a specific proximity chatroom
 */
export async function getProximityChatroom(id: number, location?: { latitude: number; longitude: number }): Promise<ProximityChatroom> {
  const params = new URLSearchParams();
  if (location) {
    params.append('latitude', location.latitude.toString());
    params.append('longitude', location.longitude.toString());
  }

  const response = await apiClient.get<ProximityChatroom>(`/proximity-chatrooms/${id}?${params.toString()}`);
  return response.data;
}

/**
 * Join a proximity chatroom
 */
export async function joinProximityChatroom(id: number, data: JoinProximityChatroomRequest): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/proximity-chatrooms/${id}/join`, data);
  return response.data;
}

/**
 * Leave a proximity chatroom
 */
export async function leaveProximityChatroom(id: number): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/proximity-chatrooms/${id}/leave`);
  return response.data;
}

/**
 * Update member location
 */
export async function updateLocation(id: number, data: UpdateLocationRequest): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/proximity-chatrooms/${id}/location`, data);
  return response.data;
}

/**
 * Get proximity chatroom members
 */
export async function getProximityChatroomMembers(id: number, filters: { networking_only?: boolean; social_only?: boolean } = {}): Promise<MemberResponse> {
  const params = new URLSearchParams();
  if (filters.networking_only) params.append('networking_only', 'true');
  if (filters.social_only) params.append('social_only', 'true');

  const response = await apiClient.get<MemberResponse>(`/proximity-chatrooms/${id}/members?${params.toString()}`);
  return response.data;
}

/**
 * Get nearby networking members
 */
export async function getNearbyNetworking(id: number, data: NearbyNetworkingRequest): Promise<NearbyNetworkingResponse> {
  const params = new URLSearchParams();
  params.append('latitude', data.latitude.toString());
  params.append('longitude', data.longitude.toString());
  if (data.radius_meters) params.append('radius_meters', data.radius_meters.toString());

  const response = await apiClient.get<NearbyNetworkingResponse>(`/proximity-chatrooms/${id}/networking?${params.toString()}`);
  return response.data;
}

/**
 * Get proximity chatroom analytics
 */
export async function getProximityAnalytics(id: number): Promise<ProximityAnalytics> {
  const response = await apiClient.get<ProximityAnalytics>(`/proximity-chatrooms/${id}/analytics`);
  return response.data;
}

/**
 * Get messages for a proximity chatroom
 */
export async function getProximityChatroomMessages(
  chatroomId: number,
  filters: {
    type?: string;
    user_id?: number;
    networking_only?: boolean;
    social_only?: boolean;
    pinned?: boolean;
    announcements?: boolean;
  } = {}
): Promise<MessageResponse> {
  const params = new URLSearchParams();
  
  if (filters.type) params.append('type', filters.type);
  if (filters.user_id) params.append('user_id', filters.user_id.toString());
  if (filters.networking_only) params.append('networking_only', 'true');
  if (filters.social_only) params.append('social_only', 'true');
  if (filters.pinned !== undefined) params.append('pinned', filters.pinned.toString());
  if (filters.announcements !== undefined) params.append('announcements', filters.announcements.toString());

  const response = await apiClient.get<MessageResponse>(`/proximity-chatrooms/${chatroomId}/messages?${params.toString()}`);
  return response.data;
}

/**
 * Send a message to a proximity chatroom
 */
export async function sendProximityMessage(chatroomId: number, data: SendProximityMessageRequest): Promise<ProximityChatroomMessage> {
  const response = await apiClient.post<ProximityChatroomMessage>(`/proximity-chatrooms/${chatroomId}/messages`, data);
  return response.data;
}

/**
 * Get a specific message
 */
export async function getProximityMessage(chatroomId: number, messageId: number): Promise<ProximityChatroomMessage> {
  const response = await apiClient.get<ProximityChatroomMessage>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}`);
  return response.data;
}

/**
 * Edit a message
 */
export async function editProximityMessage(
  chatroomId: number,
  messageId: number,
  data: { content: string; is_networking?: boolean; is_social?: boolean }
): Promise<ProximityChatroomMessage> {
  const response = await apiClient.put<ProximityChatroomMessage>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}`, data);
  return response.data;
}

/**
 * Delete a message
 */
export async function deleteProximityMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.delete<{ message: string }>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}`);
  return response.data;
}

/**
 * Add reaction to a message
 */
export async function addProximityReaction(
  chatroomId: number,
  messageId: number,
  data: AddReactionRequest
): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}/reactions`, data);
  return response.data;
}

/**
 * Remove reaction from a message
 */
export async function removeProximityReaction(
  chatroomId: number,
  messageId: number,
  data: AddReactionRequest
): Promise<{ message: string }> {
  const response = await apiClient.delete<{ message: string }>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}/reactions`, { body: JSON.stringify(data) });
  return response.data;
}

/**
 * Pin a message (moderator only)
 */
export async function pinProximityMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}/pin`);
  return response.data;
}

/**
 * Unpin a message (moderator only)
 */
export async function unpinProximityMessage(chatroomId: number, messageId: number): Promise<{ message: string }> {
  const response = await apiClient.delete<{ message: string }>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}/pin`);
  return response.data;
}

/**
 * Get pinned messages for a proximity chatroom
 */
export async function getPinnedProximityMessages(chatroomId: number): Promise<ProximityChatroomMessage[]> {
  const response = await apiClient.get<ProximityChatroomMessage[]>(`/proximity-chatrooms/${chatroomId}/messages/pinned`);
  return response.data;
}

/**
 * Get networking messages
 */
export async function getNetworkingMessages(chatroomId: number): Promise<MessageResponse> {
  const response = await apiClient.get<MessageResponse>(`/proximity-chatrooms/${chatroomId}/messages/networking`);
  return response.data;
}

/**
 * Get social messages
 */
export async function getSocialMessages(chatroomId: number): Promise<MessageResponse> {
  const response = await apiClient.get<MessageResponse>(`/proximity-chatrooms/${chatroomId}/messages/social`);
  return response.data;
}

/**
 * Get message replies
 */
export async function getProximityMessageReplies(chatroomId: number, messageId: number): Promise<ProximityChatroomMessage[]> {
  const response = await apiClient.get<ProximityChatroomMessage[]>(`/proximity-chatrooms/${chatroomId}/messages/${messageId}/replies`);
  return response.data;
}
