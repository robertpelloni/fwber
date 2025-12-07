'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api-client';

export default function FriendList() {
  const queryClient = useQueryClient();

  const { data: friends, isLoading } = useQuery({
    queryKey: ['friends'],
    queryFn: () => apiClient.get('/friends').then((res) => res.data),
  });

  const removeFriend = useMutation({
    mutationFn: (friendId: number) => apiClient.delete(`/friends/${friendId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['friends'] });
    },
  });

  if (isLoading) return <div>Loading...</div>;

  return (
    <div>
      {friends?.map((friend: any) => (
        <div key={friend.id} className="flex items-center justify-between p-2 border-b">
          <span>{friend.name}</span>
          <button
            onClick={() => removeFriend.mutate(friend.id)}
            className="px-2 py-1 bg-red-500 text-white rounded"
          >
            Remove
          </button>
        </div>
      ))}
    </div>
  );
}
