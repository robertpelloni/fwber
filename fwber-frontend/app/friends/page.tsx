'use client';

import { useState, useEffect, useMemo, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { getFriends, getFriendRequests, sendFriendRequest, respondToFriendRequest, removeFriend } from '@/lib/api/friends';
import FriendList from '@/components/friends/FriendList';
import FriendRequestList from '@/components/friends/FriendRequestList';
import UserSearch from '@/components/friends/UserSearch';
import { searchUsers, type UserProfile } from '@/lib/api/profile';
import { ConnectionStatusBadge, OnlineUsersList } from '@/components/realtime';
import { Users, UserPlus, RefreshCw } from 'lucide-react';

export default function FriendsPage() {
  const { token, isAuthenticated } = useAuth();
  const [friends, setFriends] = useState<any[]>([]);
  const [friendRequests, setFriendRequests] = useState<any[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState<UserProfile[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Get friend IDs for presence tracking
  const friendIds = useMemo(() => friends.map(f => String(f.id)), [friends]);

  const fetchData = useCallback(async () => {
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
  }, [isAuthenticated, token]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const handleRefresh = async () => {
    setIsRefreshing(true);
    await fetchData();
    setIsRefreshing(false);
  };

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
          {/* Header */}
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-100 rounded-lg">
                <Users className="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900">Friends</h1>
                <div className="flex items-center gap-2 mt-1">
                  <ConnectionStatusBadge />
                  <span className="text-sm text-gray-500">•</span>
                  <span className="text-sm text-gray-500">{friends.length} friends</span>
                </div>
              </div>
            </div>
            <button
              onClick={handleRefresh}
              disabled={isRefreshing}
              className="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors disabled:opacity-50"
            >
              <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
              Refresh
            </button>
          </div>

          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
              {error}
            </div>
          )}

          {/* Online Friends Summary */}
          {friends.length > 0 && (
            <div className="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
              <div className="flex items-center gap-2 mb-2">
                <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                <span className="font-medium text-green-800">Friends Online</span>
              </div>
              <OnlineUsersList userIds={friendIds} maxDisplay={8} showCount />
            </div>
          )}

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
