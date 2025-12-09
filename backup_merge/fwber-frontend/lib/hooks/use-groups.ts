import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getGroups,
  getMyGroups,
  getGroup,
  createGroup,
  joinGroup,
  leaveGroup,
  getGroupPosts,
  createGroupPost,
  deleteGroupPost,
  CreateGroupRequest,
  CreateGroupPostRequest,
} from '../api/groups';

export function useGroups() {
  return useQuery({
    queryKey: ['groups'],
    queryFn: getGroups,
  });
}

export function useMyGroups() {
  return useQuery({
    queryKey: ['groups', 'my'],
    queryFn: getMyGroups,
  });
}

export function useGroup(id: number) {
  return useQuery({
    queryKey: ['groups', id],
    queryFn: () => getGroup(id),
    enabled: !!id,
  });
}

export function useCreateGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateGroupRequest) => createGroup(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['groups'] });
      queryClient.invalidateQueries({ queryKey: ['groups', 'my'] });
    },
  });
}

export function useJoinGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => joinGroup(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: ['groups', id] });
      queryClient.invalidateQueries({ queryKey: ['groups', 'my'] });
      queryClient.invalidateQueries({ queryKey: ['groups'] });
    },
  });
}

export function useLeaveGroup() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => leaveGroup(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: ['groups', id] });
      queryClient.invalidateQueries({ queryKey: ['groups', 'my'] });
      queryClient.invalidateQueries({ queryKey: ['groups'] });
    },
  });
}

export function useGroupPosts(groupId: number) {
  return useQuery({
    queryKey: ['groups', groupId, 'posts'],
    queryFn: () => getGroupPosts(groupId),
    enabled: !!groupId,
  });
}

export function useCreateGroupPost(groupId: number) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateGroupPostRequest) => createGroupPost(groupId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['groups', groupId, 'posts'] });
    },
  });
}

export function useDeleteGroupPost() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (postId: number) => deleteGroupPost(postId),
    onSuccess: () => {
      // We need to invalidate the posts query, but we don't know the groupId here easily unless passed.
      // We can invalidate all group posts or pass groupId in context.
      // For simplicity, we'll invalidate all group posts queries.
      queryClient.invalidateQueries({ queryKey: ['groups'] });
    },
  });
}
