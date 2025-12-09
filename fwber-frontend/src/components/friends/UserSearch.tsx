'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api-client';

export default function UserSearch() {
  const [query, setQuery] = useState('');
  const queryClient = useQueryClient();

  const { data: users, refetch } = useQuery({
    queryKey: ['userSearch', query],
    queryFn: () => apiClient.get(`/friends/search?query=${encodeURIComponent(query)}`).then((res) => res.data),
    enabled: false,
  });

  const sendRequest = useMutation({
    mutationFn: (friendId: number) => apiClient.post('/friends/requests', { friend_id: friendId }),
    onSuccess: () => {
      alert('Friend request sent!');
    },
    onError: (error: any) => {
      alert(error.response.data.message);
    },
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (query.trim()) {
      refetch();
    }
  };

  return (
    <div>
      <form onSubmit={handleSearch} className="flex mb-4">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search for users..."
          className="border p-2 flex-grow"
        />
        <button type="submit" className="px-4 py-2 bg-blue-500 text-white rounded">
          Search
        </button>
      </form>
      <div>
        {users?.map((user: any) => (
          <div key={user.id} className="flex items-center justify-between p-2 border-b">
            <span>{user.name}</span>
            <button
              onClick={() => sendRequest.mutate(user.id)}
              className="px-2 py-1 bg-blue-500 text-white rounded"
            >
              Add Friend
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
