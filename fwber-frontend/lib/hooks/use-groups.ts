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
  getGroupMatches,
  requestGroupMatch,
  getGroupMatchRequests,
  getConnectedGroups,
  acceptGroupMatchRequest,
  rejectGroupMatchRequest,
  getGroupEvents, // Added
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

export function useGroupMatches(groupId: number) {
  return useQuery({
    queryKey: ['groups', groupId, 'matches'],
    queryFn: () => getGroupMatches(groupId),
    enabled: !!groupId,
  });
}

export function useRequestGroupMatch(groupId: number) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (targetGroupId: number) => requestGroupMatch(groupId, targetGroupId),
    onSuccess: () => {
      // Invalidate matches list to reflect status changes if any (e.g. if we show pending status)
      queryClient.invalidateQueries({ queryKey: ['groups', groupId, 'matches'] });
      queryClient.invalidateQueries({ queryKey: ['groups', groupId, 'requests'] });
    },
  });
}

export function useGroupMatchRequests(groupId: number) {
    return useQuery({
        queryKey: ['groups', groupId, 'requests'],
        queryFn: () => getGroupMatchRequests(groupId),
        enabled: !!groupId,
    });
}

export function useConnectedGroups(groupId: number) {
    return useQuery({
        queryKey: ['groups', groupId, 'connected'],
        queryFn: () => getConnectedGroups(groupId),
        enabled: !!groupId,
    });
}

export function useAcceptMatchRequest(groupId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (matchId: number) => acceptGroupMatchRequest(groupId, matchId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['groups', groupId, 'requests'] });
            // Potentially invalidate connections/established matches list if we add one
        },
    });
}

export function useRejectMatchRequest(groupId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (matchId: number) => rejectGroupMatchRequest(groupId, matchId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['groups', groupId, 'requests'] });
        },
    });
}

export function useGroupEvents(groupId: number) {
    return useQuery({
        queryKey: ['groups', groupId, 'events'],
        queryFn: () => getGroupEvents(groupId),
        enabled: !!groupId,
    });
}
