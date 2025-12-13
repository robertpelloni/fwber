'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import FriendList from '@/components/friends/FriendList';
import FriendRequestList from '@/components/friends/FriendRequestList';
import UserSearch from '@/components/friends/UserSearch';

export default function FriendsPage() {
  const [activeTab, setActiveTab] = useState('friends');
  const [searchQuery, setSearchQuery] = useState('');
  const queryClient = useQueryClient();

  // Friends Query
  const { data: friends, isLoading: friendsLoading } = useQuery({
    queryKey: ['friends'],
    queryFn: () => api.get<any[]>('/friends'),
    enabled: activeTab === 'friends',
  });

  // Requests Query
  const { data: requests, isLoading: requestsLoading } = useQuery({
    queryKey: ['friend-requests'],
    queryFn: () => api.get<any[]>('/friends/requests'),
    enabled: activeTab === 'requests',
  });

  // Search Query
  const { data: searchResults, isLoading: searchLoading } = useQuery({
    queryKey: ['users', searchQuery],
    queryFn: () => api.get<any[]>(`/users/search?q=${searchQuery}`),
    enabled: activeTab === 'search' && !!searchQuery,
  });

  // Mutations
  const removeFriend = useMutation({
    mutationFn: (friendId: number) => api.delete(`/friends/${friendId}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['friends'] }),
  });

  const respondToRequest = useMutation({
    mutationFn: ({ userId, status }: { userId: number, status: 'accepted' | 'declined' }) => 
      api.post(`/friends/requests/${userId}/respond`, { status }),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['friend-requests'] });
        queryClient.invalidateQueries({ queryKey: ['friends'] });
    },
  });

  const sendRequest = useMutation({
    mutationFn: (userId: number) => api.post(`/friends/requests/${userId}`),
    onSuccess: () => alert('Request sent!'),
  });

  return (
    <div className="container mx-auto p-4">
      <h1 className="text-2xl font-bold mb-4">Friends</h1>
      <div className="flex border-b mb-4">
        <button
          className={`px-4 py-2 ${activeTab === 'friends' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('friends')}
        >
          My Friends
        </button>
        <button
          className={`px-4 py-2 ${activeTab === 'requests' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('requests')}
        >
          Friend Requests
        </button>
        <button
          className={`px-4 py-2 ${activeTab === 'search' ? 'border-b-2 border-blue-500' : ''}`}
          onClick={() => setActiveTab('search')}
        >
          Search Users
        </button>
      </div>

      <div>
        {activeTab === 'friends' && (
            friendsLoading ? <div>Loading...</div> :
            <FriendList 
                friends={friends || []} 
                onRemoveFriend={(id) => removeFriend.mutate(id)} 
            />
        )}
        {activeTab === 'requests' && (
            requestsLoading ? <div>Loading...</div> :
            <FriendRequestList 
                friendRequests={requests || []} 
                onRespondToRequest={(userId, status) => respondToRequest.mutate({ userId, status })} 
            />
        )}
        {activeTab === 'search' && (
            <div>
                <input 
                    type="text" 
                    placeholder="Search users..." 
                    className="border p-2 mb-4 w-full"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                />
                {searchLoading ? <div>Searching...</div> :
                <UserSearch 
                    searchResults={searchResults || []} 
                    onSendRequest={(id) => sendRequest.mutate(id)} 
                />}
            </div>
        )}
      </div>
    </div>
  );
}
