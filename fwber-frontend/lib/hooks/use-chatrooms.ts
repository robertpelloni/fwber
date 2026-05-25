import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getChatrooms,
  getChatroom,
  createChatroom,
  joinChatroom,
  leaveChatroom,
  getChatroomMembers,
  updateChatroom,
  deleteChatroom,
  getMyChatrooms,
  getChatroomCategories,
  getPopularChatrooms,
  searchChatrooms,
  getChatroomMessages,
  sendMessage,
  getMessage,
  editMessage,
  deleteMessage,
  addReaction,
  removeReaction,
  pinMessage,
  unpinMessage,
  getPinnedMessages,
  getMessageReplies,
  type Chatroom,
  type ChatroomMessage,
  type ChatroomMember,
  type CreateChatroomRequest,
  type SendMessageRequest,
  type AddReactionRequest,
  type ChatroomFilters,
  type MessageFilters,
} from '../api/chatrooms';

// Query keys
export const chatroomKeys = {
  all: ['chatrooms'] as const,
  lists: () => [...chatroomKeys.all, 'list'] as const,
  list: (filters: ChatroomFilters) => [...chatroomKeys.lists(), filters] as const,
  details: () => [...chatroomKeys.all, 'detail'] as const,
  detail: (id: number) => [...chatroomKeys.details(), id] as const,
  messages: (chatroomId: number, filters: MessageFilters) => [...chatroomKeys.detail(chatroomId), 'messages', filters] as const,
  members: (chatroomId: number) => [...chatroomKeys.detail(chatroomId), 'members'] as const,
  my: () => [...chatroomKeys.all, 'my'] as const,
  categories: () => [...chatroomKeys.all, 'categories'] as const,
  popular: () => [...chatroomKeys.all, 'popular'] as const,
  search: (query: string, filters: ChatroomFilters) => [...chatroomKeys.all, 'search', query, filters] as const,
  pinned: (chatroomId: number) => [...chatroomKeys.detail(chatroomId), 'pinned'] as const,
  replies: (chatroomId: number, messageId: number) => [...chatroomKeys.detail(chatroomId), 'replies', messageId] as const,
};

// Chatrooms list
export function useChatrooms(filters: ChatroomFilters = {}) {
  return useQuery({
    queryKey: chatroomKeys.list(filters),
  queryFn: () => getChatrooms(filters),
  staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Single chatroom
export function useChatroom(id: number) {
  return useQuery({
    queryKey: chatroomKeys.detail(id),
    queryFn: () => getChatroom(id),
    enabled: !!id,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// My chatrooms
export function useMyChatrooms() {
  return useQuery({
    queryKey: chatroomKeys.my(),
    queryFn: getMyChatrooms,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Chatroom categories
export function useChatroomCategories() {
  return useQuery({
    queryKey: chatroomKeys.categories(),
    queryFn: getChatroomCategories,
    staleTime: 60 * 60 * 1000, // 1 hour
  });
}

// Popular chatrooms
export function usePopularChatrooms() {
  return useQuery({
    queryKey: chatroomKeys.popular(),
    queryFn: getPopularChatrooms,
    staleTime: 10 * 60 * 1000, // 10 minutes
  });
}

// Search chatrooms
export function useSearchChatrooms(query: string, filters: ChatroomFilters = {}) {
  return useQuery({
    queryKey: chatroomKeys.search(query, filters),
    queryFn: () => searchChatrooms(query, filters),
    enabled: query.length >= 2,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Chatroom messages
export function useChatroomMessages(chatroomId: number, filters: MessageFilters = {}) {
  return useQuery({
    queryKey: chatroomKeys.messages(chatroomId, filters),
    queryFn: () => getChatroomMessages(chatroomId, filters),
    enabled: !!chatroomId,
    staleTime: 30 * 1000, // 30 seconds
  });
}

// Chatroom members
export function useChatroomMembers(chatroomId: number) {
  return useQuery({
    queryKey: chatroomKeys.members(chatroomId),
    queryFn: () => getChatroomMembers(chatroomId),
    enabled: !!chatroomId,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Pinned messages
export function usePinnedMessages(chatroomId: number) {
  return useQuery({
    queryKey: chatroomKeys.pinned(chatroomId),
    queryFn: () => getPinnedMessages(chatroomId),
    enabled: !!chatroomId,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// Message replies
export function useMessageReplies(chatroomId: number, messageId: number) {
  return useQuery({
    queryKey: chatroomKeys.replies(chatroomId, messageId),
    queryFn: () => getMessageReplies(chatroomId, messageId),
    enabled: !!chatroomId && !!messageId,
    staleTime: 2 * 60 * 1000, // 2 minutes
  });
}

// Mutations
export function useCreateChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateChatroomRequest) => createChatroom(data),
    onSuccess: (newChatroom) => {
      // Invalidate and refetch chatrooms lists
      queryClient.invalidateQueries({ queryKey: chatroomKeys.lists() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.my() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.popular() });
      
      // Add the new chatroom to the cache
      queryClient.setQueryData(chatroomKeys.detail(newChatroom.id), {
        chatroom: newChatroom,
        messages: { data: [], current_page: 1, last_page: 1, per_page: 50, total: 0 }
      });
    },
  });
}

export function useJoinChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => joinChatroom(id),
    onSuccess: (_, chatroomId) => {
      // Invalidate chatroom data
      queryClient.invalidateQueries({ queryKey: chatroomKeys.detail(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.members(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.my() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.lists() });
    },
  });
}

export function useLeaveChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => leaveChatroom(id),
    onSuccess: (_, chatroomId) => {
      // Invalidate chatroom data
      queryClient.invalidateQueries({ queryKey: chatroomKeys.detail(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.members(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.my() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.lists() });
    },
  });
}

export function useUpdateChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateChatroomRequest> }) =>
      updateChatroom(id, data),
    onSuccess: (updatedChatroom, { id }) => {
      // Update the chatroom in cache
      queryClient.setQueryData(chatroomKeys.detail(id), (oldData: any) => ({
        ...oldData,
        chatroom: updatedChatroom,
      }));
      
      // Invalidate lists
      queryClient.invalidateQueries({ queryKey: chatroomKeys.lists() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.my() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.popular() });
    },
  });
}

export function useDeleteChatroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => deleteChatroom(id),
    onSuccess: (_, chatroomId) => {
      // Remove from cache
      queryClient.removeQueries({ queryKey: chatroomKeys.detail(chatroomId) });
      
      // Invalidate lists
      queryClient.invalidateQueries({ queryKey: chatroomKeys.lists() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.my() });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.popular() });
    },
  });
}

export function useSendMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, data }: { chatroomId: number; data: SendMessageRequest }) =>
      sendMessage(chatroomId, data),
    onSuccess: (newMessage, { chatroomId }) => {
      // Add message to the chatroom messages cache
      queryClient.setQueryData(
        chatroomKeys.messages(chatroomId, {}),
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
      queryClient.invalidateQueries({ queryKey: chatroomKeys.detail(chatroomId) });
    },
  });
}

export function useEditMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, content }: { chatroomId: number; messageId: number; content: string }) =>
      editMessage(chatroomId, messageId, content),
    onSuccess: (updatedMessage, { chatroomId }) => {
      // Update message in cache
      queryClient.setQueryData(
        chatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: oldData.data.map((msg: ChatroomMessage) =>
              msg.id === updatedMessage.id ? updatedMessage : msg
            ),
          };
        }
      );
    },
  });
}

export function useDeleteMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      deleteMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId, messageId }) => {
      // Update message in cache to show as deleted
      queryClient.setQueryData(
        chatroomKeys.messages(chatroomId, {}),
        (oldData: any) => {
          if (!oldData) return oldData;
          
          return {
            ...oldData,
            data: oldData.data.map((msg: ChatroomMessage) =>
              msg.id === messageId ? { ...msg, is_deleted: true, content: '[Message deleted]' } : msg
            ),
          };
        }
      );
    },
  });
}

export function useAddReaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, data }: { chatroomId: number; messageId: number; data: AddReactionRequest }) =>
      addReaction(chatroomId, messageId, data),
    onSuccess: (_, { chatroomId, messageId }) => {
      // Invalidate messages to refetch with updated reactions
      queryClient.invalidateQueries({ queryKey: chatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function useRemoveReaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId, data }: { chatroomId: number; messageId: number; data: AddReactionRequest }) =>
      removeReaction(chatroomId, messageId, data),
    onSuccess: (_, { chatroomId, messageId }) => {
      // Invalidate messages to refetch with updated reactions
      queryClient.invalidateQueries({ queryKey: chatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function usePinMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      pinMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate pinned messages
      queryClient.invalidateQueries({ queryKey: chatroomKeys.pinned(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.messages(chatroomId, {}) });
    },
  });
}

export function useUnpinMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ chatroomId, messageId }: { chatroomId: number; messageId: number }) =>
      unpinMessage(chatroomId, messageId),
    onSuccess: (_, { chatroomId }) => {
      // Invalidate pinned messages
      queryClient.invalidateQueries({ queryKey: chatroomKeys.pinned(chatroomId) });
      queryClient.invalidateQueries({ queryKey: chatroomKeys.messages(chatroomId, {}) });
    },
  });
}
