'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api-client';

export default function FriendRequestList() {
  const queryClient = useQueryClient();

  const { data: requests, isLoading } = useQuery({
    queryKey: ['friendRequests'],
    queryFn: () => apiClient.get('/friends/requests').then((res) => res.data),
  });

  const respondToRequest = useMutation({
    mutationFn: ({ userId, status }: { userId: number; status: string }) =>
      apiClient.post(`/friends/requests/${userId}`, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['friendRequests'] });
      queryClient.invalidateQueries({ queryKey: ['friends'] });
    },
  });

  if (isLoading) return <div>Loading...</div>;

  return (
    <div>
      {requests?.map((request: any) => (
        <div key={request.id} className="flex items-center justify-between p-2 border-b">
          <span>{request.user.name}</span>
          <div>
            <button
              onClick={() => respondToRequest.mutate({ userId: request.user.id, status: 'accepted' })}
              className="px-2 py-1 bg-green-500 text-white rounded mr-2"
            >
              Accept
            </button>
            <button
              onClick={() => respondToRequest.mutate({ userId: request.user.id, status: 'declined' })}
              className="px-2 py-1 bg-red-500 text-white rounded"
            >
              Decline
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
