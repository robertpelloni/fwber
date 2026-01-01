import { apiClient } from './client';
import { PaginatedResponse } from './types';

export interface Group {
  id: number;
  name: string;
  description?: string;
  icon?: string;
  privacy: 'public' | 'private';
  category?: string;
  tags?: string[];
  matching_enabled?: boolean;
  location_lat?: number;
  location_lng?: number;
  member_count: number;
  created_by_user_id: number;
  created_at: string;
  updated_at: string;
  chatroom_id?: number;
  members?: GroupMember[];
  posts?: GroupPost[];
  user_role?: 'admin' | 'moderator' | 'member' | 'owner';
  is_member?: boolean; // Helper if backend returns it, or we check locally
}

export interface GroupMember {
  id: number;
  group_id: number;
  user_id: number;
  role: 'admin' | 'moderator' | 'member';
  joined_at: string;
  user?: {
    id: number;
    name: string;
    avatar_url?: string;
  };
}

export interface GroupPost {
  id: number;
  group_id: number;
  user_id: number;
  content: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    avatar_url?: string;
  };
}

export interface CreateGroupRequest {
  name: string;
  description?: string;
  icon?: string;
  privacy: 'public' | 'private';
  category?: string;
  tags?: string[];
  matching_enabled?: boolean;
  location_lat?: number;
  location_lng?: number;
}

export interface CreateGroupPostRequest {
  content: string;
}

export async function getGroups(): Promise<PaginatedResponse<Group>> {
  const response = await apiClient.get<PaginatedResponse<Group>>('/groups');
  return response.data;
}

export async function getMyGroups(): Promise<PaginatedResponse<Group>> {
  const response = await apiClient.get<PaginatedResponse<Group>>('/groups/my-groups');
  return response.data;
}

export async function getGroup(id: number): Promise<Group> {
  const response = await apiClient.get<Group>(`/groups/${id}`);
  return response.data;
}

export async function createGroup(data: CreateGroupRequest): Promise<Group> {
  const response = await apiClient.post<Group>('/groups', data);
  return response.data;
}

export async function joinGroup(id: number): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/groups/${id}/join`);
  return response.data;
}

export async function leaveGroup(id: number): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/groups/${id}/leave`);
  return response.data;
}

export async function getGroupPosts(groupId: number): Promise<GroupPost[]> {
  const response = await apiClient.get<GroupPost[]>(`/groups/${groupId}/posts`);
  return response.data;
}

export async function createGroupPost(groupId: number, data: CreateGroupPostRequest): Promise<GroupPost> {
  const response = await apiClient.post<GroupPost>(`/groups/${groupId}/posts`, data);
  return response.data;
}

export async function deleteGroupPost(postId: number): Promise<{ message: string }> {
  const response = await apiClient.delete<{ message: string }>(`/groups/posts/${postId}`);
  return response.data;
}

export async function getGroupMatches(groupId: number): Promise<Group[]> {
  const response = await apiClient.get<{ matches: Group[] }>(`/groups/${groupId}/matches`);
  return response.data.matches;
}

export async function requestGroupMatch(groupId: number, targetGroupId: number): Promise<{ message: string }> {
  const response = await apiClient.post<{ message: string }>(`/groups/${groupId}/matches/${targetGroupId}/connect`);
  return response.data;
}

export async function getGroupMatchRequests(groupId: number): Promise<{ incoming: any[], outgoing: any[] }> {
    const response = await apiClient.get<{ incoming: any[], outgoing: any[] }>(`/groups/${groupId}/matches/requests`);
    return response.data;
}

export async function getConnectedGroups(groupId: number): Promise<Group[]> {
    const response = await apiClient.get<{ connected: Group[] }>(`/groups/${groupId}/matches/connected`);
    return response.data.connected;
}

export async function acceptGroupMatchRequest(groupId: number, matchId: number): Promise<{ message: string }> {
    const response = await apiClient.post<{ message: string }>(`/groups/${groupId}/matches/requests/${matchId}/accept`);
    return response.data;
}

export async function rejectGroupMatchRequest(groupId: number, matchId: number): Promise<{ message: string }> {
    const response = await apiClient.post<{ message: string }>(`/groups/${groupId}/matches/requests/${matchId}/reject`);
    return response.data;
}

export async function getGroupEvents(groupId: number): Promise<any[]> {
    const response = await apiClient.get<{ events: any[] }>(`/groups/${groupId}/events`);
    return response.data.events;
}
