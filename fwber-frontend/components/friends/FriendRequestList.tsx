'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { getFriendRequests, respondToFriendRequest } from '@/lib/api/friends';
import { User } from '@/types/user';

interface FriendRequest extends User {
  // Add any additional properties from the friend request object if needed
}

export default function FriendRequestList() {
  const { token, isAuthenticated } = useAuth();
  const [friendRequests, setFriendRequests] = useState<FriendRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = async () => {
    if (isAuthenticated && token) {
      try {
        setIsLoading(true);
        const friendRequestsData = await getFriendRequests(token);
        setFriendRequests(friendRequestsData);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch friend requests');
      } finally {
        setIsLoading(false);
      }
    }
  };

  useEffect(() => {
    fetchData();
  }, [isAuthenticated, token]);

  const handleRespondToRequest = async (userId: number, status: 'accepted' | 'declined') => {
    if (token) {
      try {
        await respondToFriendRequest(token, userId, status);
        fetchData(); // Refresh data
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to respond to friend request');
      }
    }
  };

  if (isLoading) {
    return <div>Loading friend requests...</div>;
  }

  if (error) {
    return <div className="text-red-500">{error}</div>;
  }

  return (
    <div>
      {friendRequests.length === 0 ? (
        <p>You don't have any pending friend requests.</p>
      ) : (
        <ul className="space-y-4">
          {friendRequests.map((request) => (
            <li key={request.id} className="flex items-center justify-between p-4 bg-white rounded-lg shadow">
              <div>
                <p className="font-semibold">{request.name}</p>
                <p className="text-sm text-gray-500">{request.email}</p>
              </div>
              <div className="flex space-x-2">
                <button
                  onClick={() => handleRespondToRequest(request.id, 'accepted')}
                  className="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
                >
                  Accept
                </button>
                <button
                  onClick={() => handleRespondToRequest(request.id, 'declined')}
                  className="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
                >
                  Decline
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
