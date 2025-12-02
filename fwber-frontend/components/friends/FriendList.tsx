'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { getFriends, removeFriend } from '@/lib/api/friends';
import { User } from '@/types/user';

export default function FriendList() {
  const { token, isAuthenticated } = useAuth();
  const [friends, setFriends] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = async () => {
    if (isAuthenticated && token) {
      try {
        setIsLoading(true);
        const friendsData = await getFriends(token);
        setFriends(friendsData);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch friends');
      } finally {
        setIsLoading(false);
      }
    }
  };

  useEffect(() => {
    fetchData();
  }, [isAuthenticated, token]);

  const handleRemoveFriend = async (friendId: number) => {
    if (token) {
      try {
        await removeFriend(token, friendId);
        fetchData(); // Refresh data
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to remove friend');
      }
    }
  };

  if (isLoading) {
    return <div>Loading friends...</div>;
  }

  if (error) {
    return <div className="text-red-500">{error}</div>;
  }

  return (
    <div>
      {friends.length === 0 ? (
        <p>You don't have any friends yet.</p>
      ) : (
        <ul className="space-y-4">
          {friends.map((friend) => (
            <li key={friend.id} className="flex items-center justify-between p-4 bg-white rounded-lg shadow">
              <div>
                <p className="font-semibold">{friend.name}</p>
                <p className="text-sm text-gray-500">{friend.email}</p>
              </div>
              <button
                onClick={() => handleRemoveFriend(friend.id)}
                className="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
              >
                Remove
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
