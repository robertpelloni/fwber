import { apiClient } from './client';

export interface Group {
  id: number;
  name: string;
  description?: string;
  icon?: string;
  privacy: 'public' | 'private';
  member_count: number;
  created_by_user_id: number;
  created_at: string;
  updated_at: string;
  members?: GroupMember[];
  posts?: GroupPost[];
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
}

export interface CreateGroupPostRequest {
  content: string;
}

export async function getGroups(): Promise<Group[]> {
  const response = await apiClient.get<Group[]>('/groups');
  return response.data;
}

export async function getMyGroups(): Promise<Group[]> {
  const response = await apiClient.get<Group[]>('/groups/my-groups');
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
