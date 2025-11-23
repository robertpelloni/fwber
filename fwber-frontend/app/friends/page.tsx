'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { getFriends, getFriendRequests, sendFriendRequest, respondToFriendRequest, removeFriend } from '@/lib/api/friends';
import FriendList from '@/components/friends/FriendList';
import FriendRequestList from '@/components/friends/FriendRequestList';
import UserSearch from '@/components/friends/UserSearch';
import { searchUsers } from '@/lib/api/profile';

export default function FriendsPage() {
  const { token, isAuthenticated } = useAuth();
  const [friends, setFriends] = useState([]);
  const [friendRequests, setFriendRequests] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = async () => {
    if (isAuthenticated && token) {
      try {
        setIsLoading(true);
        const [friendsData, friendRequestsData] = await Promise.all([
          getFriends(token),
          getFriendRequests(token),
        ]);
        setFriends(friendsData);
        setFriendRequests(friendRequestsData);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to fetch data');
      } finally {
        setIsLoading(false);
      }
    }
  };

  useEffect(() => {
    fetchData();
  }, [isAuthenticated, token]);

  const handleSearch = async () => {
    if (token) {
      try {
        const results = await searchUsers(token, searchTerm);
        setSearchResults(results);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to search for users');
      }
    }
  };

  const handleSendRequest = async (userId: number) => {
    if (token) {
      try {
        await sendFriendRequest(token, userId);
        alert('Friend request sent!');
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to send friend request');
      }
    }
  };

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

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-8">Friends</h1>

          {error && <div className="text-red-500 mb-4">{error}</div>}

          {/* Search Bar */}
          <div className="mb-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Find Friends</h2>
            <div className="flex">
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="flex-grow border-gray-300 rounded-l-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Search by name or email"
              />
              <button
                onClick={handleSearch}
                className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-r-md"
              >
                Search
              </button>
            </div>
            <UserSearch searchResults={searchResults} onSendRequest={handleSendRequest} />
          </div>

          {/* Friend Requests */}
          <div className="mb-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Friend Requests</h2>
            <FriendRequestList friendRequests={friendRequests} onRespondToRequest={handleRespondToRequest} />
          </div>

          {/* Friend List */}
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Your Friends</h2>
            <FriendList friends={friends} onRemoveFriend={handleRemoveFriend} />
          </div>
        </div>
      </div>
    </ProtectedRoute>
  );
}
