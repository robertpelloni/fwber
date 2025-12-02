'use client';

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import { searchUsers } from '@/lib/api/profile';
import { sendFriendRequest } from '@/lib/api/friends';
import { User } from '@/types/user';

export default function UserSearch() {
  const { token } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSearch = async () => {
    if (token) {
      try {
        setIsLoading(true);
        const results = await searchUsers(token, searchTerm);
        setSearchResults(results);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to search for users');
      } finally {
        setIsLoading(false);
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

  return (
    <div>
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
          {isLoading ? 'Searching...' : 'Search'}
        </button>
      </div>

      {error && <div className="text-red-500 mt-4">{error}</div>}

      <ul className="space-y-4 mt-8">
        {searchResults.map((user) => (
          <li key={user.id} className="flex items-center justify-between p-4 bg-white rounded-lg shadow">
            <div>
              <p className="font-semibold">{user.name}</p>
              <p className="text-sm text-gray-500">{user.email}</p>
            </div>
            <button
              onClick={() => handleSendRequest(user.id)}
              className="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
            >
              Send Request
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
