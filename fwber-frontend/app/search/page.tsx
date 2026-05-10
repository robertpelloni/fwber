'use client';

import { useState } from 'react';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppHeader from '@/components/AppHeader';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export default function SearchPage() {
  const { token, isAuthenticated } = useAuth();
  const [query, setQuery] = useState('');

  const { data: results, isLoading } = useQuery({
    queryKey: ['user-search', query],
    enabled: isAuthenticated && !!token && query.length >= 2,
    queryFn: async () => {
      const data = await api.get<any[]>(`/user/search?q=${encodeURIComponent(query)}`);
      return Array.isArray(data) ? data : data.users || data.data || [];
    },
  });

  const users = results || [];

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />
        <main className="max-w-2xl mx-auto px-4 py-8">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Search People</h1>
          <input
            type="text"
            value={query}
            onChange={e => setQuery(e.target.value)}
            placeholder="Search by name or username..."
            className="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent mb-6"
          />
          {isLoading ? (
            <div className="animate-pulse space-y-3">
              {[1, 2, 3].map(i => <div key={i} className="h-16 bg-gray-200 dark:bg-gray-800 rounded-lg" />)}
            </div>
          ) : query.length >= 2 && users.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 dark:text-gray-400">No results found for &quot;{query}&quot;</p>
            </div>
          ) : (
            <div className="space-y-3">
              {users.map((user: any) => (
                <a
                  key={user.id}
                  href={`/profile/${user.id}`}
                  className="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:border-orange-300 dark:hover:border-orange-600 transition-colors"
                >
                  <img
                    src={user.avatar_url || `https://api.dicebear.com/7.x/avataaars/svg?seed=${user.name}`}
                    alt={user.name}
                    className="w-12 h-12 rounded-full"
                  />
                  <div>
                    <p className="font-semibold text-gray-900 dark:text-white">{user.name}</p>
                    {user.bio && <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{user.bio}</p>}
                  </div>
                </a>
              ))}
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  );
}
