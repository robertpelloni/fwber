import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  findNearby,
  createProximityChatroom,
  getProximityChatroom,
  joinProximityChatroom,
  leaveProximityChatroom,
  updateLocation,
  getProximityChatroomMembers,
  getNearbyNetworking,
  getProximityAnalytics,
  getProximityChatroomMessages,
  sendProximityMessage,
  getProximityMessage,
  editProximityMessage,
  deleteProximityMessage,
  addProximityReaction,
  removeProximityReaction,
  pinProximityMessage,
  unpinProximityMessage,
  getPinnedProximityMessages,
  getNetworkingMessages,
  getSocialMessages,
  getProximityMessageReplies,
  type ProximityChatroom,
  type ProximityChatroomMessage,
  type ProximityChatroomMember,
  type CreateProximityChatroomRequest,
  type JoinProximityChatroomRequest,
  type SendProximityMessageRequest,
  type AddReactionRequest,
  type FindNearbyRequest,
  type UpdateLocationRequest,
  type NearbyNetworkingRequest,
} from '../api/proximity-chatrooms';

// Query keys
export const proximityChatroomKeys = {
  all: ['proximity-chatrooms'] as const,
  nearby: (filters: FindNearbyRequest) => [...proximityChatroomKeys.all, 'nearby', filters] as const,
  details: () => [...proximityChatroomKeys.all, 'detail'] as const,
  detail: (id: number) => [...proximityChatroomKeys.details(), id] as const,
  messages: (chatroomId: number, filters: any) => [...proximityChatroomKeys.detail(chatroomId), 'messages', filters] as const,
  members: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'members'] as const,
  networking: (chatroomId: number, filters: NearbyNetworkingRequest) => [...proximityChatroomKeys.detail(chatroomId), 'networking', filters] as const,
  analytics: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'analytics'] as const,
  pinned: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'pinned'] as const,
  networkingMessages: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'networking-messages'] as const,
  socialMessages: (chatroomId: number) => [...proximityChatroomKeys.detail(chatroomId), 'social-messages'] as const,
  replies: (chatroomId: number, messageId: number) => [...proximityChatroomKeys.detail(chatroomId), 'replies', messageId] as const,
};

// Find nearby proximity chatrooms
export function useNearbyProximityChatrooms(filters: FindNearbyRequest) {
  return useQuery({
    queryKey: proximityChatroomKeys.nearby(filters),
    queryFn: () => findNearby(filters),
    enabled: !!filters.latitude && !!filters.longitude,
    staleTime: 30 * 1000, // 30 seconds
  });
}

// Single proximity chatroom
export function useProximityChatroom(id: number, location?: { latitude: number; longitude: number }) {
  return useQuery({
    queryKey: proximityChatroomKeys.detail(id),
    queryFn: () => getProximityChatroom(id, location),
    enabled: !!id,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Proximity chatroom messages
export function useProximityChatroomMessages(chatroomId: number, filters: any = {}) {
  return useQuery({
    queryKey: proximityChatroomKeys.messages(chatroomId, filters),
    queryFn: () => getProximityChatroomMessages(chatroomId, filters),
    enabled: !!chatroomId,
    staleTime: 30 * 1000, // 30 seconds
  });
}

// Proximity chatroom members
export function useProximityChatroomMembers(chatroomId: number, filters: { networking_only?: boolean; social_only?: boolean } = {}) {
  return useQuery({
    queryKey: proximityChatroomKeys.members(chatroomId),
    queryFn: () => getProximityChatroomMembers(chatroomId, filters),
    enabled: !!chatroomId,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Nearby networking members
export function useNearbyNetworking(chatroomId: number, filters: NearbyNetworkingRequest) {
  return useQuery({
    queryKey: proximityChatroomKeys.networking(chatroomId, filters),
    queryFn: () => getNearbyNetworking(chatroomId, filters),
    enabled: !!chatroomId && !!filters.latitude && !!filters.longitude,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Proximity analytics
export function useProximityAnalytics(chatroomId: number) {
  return useQuery({
    queryKey: proximityChatroomKeys.analytics(chatroomId),
    queryFn: () => getProximityAnalytics(chatroomId),
    enabled: !!chatroomId,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Pinned messages
export function usePinnedProximityMessages(chatroomId: number) {
  return useQuery({
    queryKey: proximityChatroomKeys.pinned(chatroomId),
    queryFn: () => getPinnedProximityMessages(chatroomId),
    enabled: !!chatroomId,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Networking messages
export function useNetworkingMessages(chatroomId: number) {
  return useQuery({
    queryKey: proximityChatroomKeys.networkingMessages(chatroomId),
    queryFn: () => getNetworkingMessages(chatroomId),
    enabled: !!chatroomId,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Social messages
export function useSocialMessages(chatroomId: number) {
  return useQuery({
    queryKey: proximityChatroomKeys.socialMessages(chatroomId),
    queryFn: () => getSocialMessages(chatroomId),
    enabled: !!chatroomId,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Message replies
export function useProximityMessageReplies(chatroomId: number, messageId: number) {
  return useQuery({
    queryKey: proximityChatroomKeys.replies(chatroomId, messageId),
    queryFn: () => getProximityMessageReplies(chatroomId, messageId),
    enabled: !!chatroomId && !!messageId,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Mutations
export function useCreateProximityChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateProximityChatroomRequest) => createProximityChatroom(data),
    onSuccess: (newChatroom) => {
      // Invalidate nearby chatrooms
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.all });
      
      // Add the new chatroom to the cache
      queryClient.setQueryData(proximityChatroomKeys.detail(newChatroom.id), newChatroom);
    },
  });
}

export function useJoinProximityChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: JoinProximityChatroomRequest }) =>
      joinProximityChatroom(id, data),
    onSuccess: (_, { id }) => {
      // Invalidate chatroom data
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.detail(id) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.members(id) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.all });
    },
  });
}

export function useLeaveProximityChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => leaveProximityChatroom(id),
    onSuccess: (_, chatroomId) => {
      // Invalidate chatroom data
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.detail(chatroomId) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.members(chatroomId) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.all });
    },
  });
}

export function useUpdateProximityLocation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateLocationRequest }) =>
      updateLocation(id, data),
    onSuccess: (_, { id }) => {
      // Invalidate chatroom data
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.detail(id) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.members(id) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.networking(id, {} as any) });
    },
  });
}

export function useSendProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, data }: { chatroomId: number; data: SendProximityMessageRequest }) =>
      sendProximityMessage(chatroomId, data),
    onSuccess: (newMessage, { chatroomId }) => {
      // Add message to the chatroom messages cache
      queryClient.setQueryData(
        proximityChatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: [newMessage, ...oldData.data],
            total: oldData.total + 1,
          };
        }
      );
      
      // Invalidate chatroom data to update message count
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.detail(chatroomId) });
    },
  });
}

export function useEditProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, data }: { chatroomId: number; messageId: number; data: any }) =>
      editProximityMessage(chatroomId, messageId, data),
    onSuccess: (updatedMessage, { chatroomId }) => {
      // Update message in cache
      queryClient.setQueryData(
        proximityChatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: oldData.data.map((msg: ProximityChatroomMessage) =>
              msg.id === updatedMessage.id ? updatedMessage : msg
            ),
          };
        }
      );
    },
  });
}

export function useDeleteProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      deleteProximityMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId, messageId }) => {
      // Update message in cache to show as deleted
      queryClient.setQueryData(
        proximityChatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: oldData.data.map((msg: ProximityChatroomMessage) =>
              msg.id === messageId ? { ...msg, is_deleted: true, content: '[Message deleted]' } : msg
            ),
          };
        }
      );
    },
  });
}

export function useAddProximityReaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, data }: { chatroomId: number; messageId: number; data: AddReactionRequest }) =>
      addProximityReaction(chatroomId, messageId, data),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate messages to refetch with updated reactions
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function useRemoveProximityReaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, data }: { chatroomId: number; messageId: number; data: AddReactionRequest }) =>
      removeProximityReaction(chatroomId, messageId, data),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate messages to refetch with updated reactions
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function usePinProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      pinProximityMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate pinned messages
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.pinned(chatroomId) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function useUnpinProximityMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      unpinProximityMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate pinned messages
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.pinned(chatroomId) });
      queryClient.invalidateQueries({ queryKey: proximityChatroomKeys.messages(chatroomId, {}) });
    },
  });
}
