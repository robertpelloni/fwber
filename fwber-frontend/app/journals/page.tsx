'use client';

import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import AppHeader from '@/components/AppHeader';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

export default function JournalsPage() {
  const { token, isAuthenticated } = useAuth();

  const { data: journals, isLoading } = useQuery({
    queryKey: ['journals'],
    enabled: isAuthenticated && !!token,
    queryFn: async () => {
      const data = await api.get<{ entries: any[] }>('/journals');
      return data;
    },
  });

  const entries = journals?.entries || [];

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />
        <main className="max-w-4xl mx-auto px-4 py-8">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Field Notes</h1>
          {isLoading ? (
            <div className="animate-pulse space-y-4">
              {[1, 2, 3].map(i => (
                <div key={i} className="h-24 bg-gray-200 dark:bg-gray-800 rounded-lg" />
              ))}
            </div>
          ) : entries.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 dark:text-gray-400">No journal entries yet. Start writing your story!</p>
            </div>
          ) : (
            <div className="space-y-4">
              {entries.map((entry: any) => (
                <div key={entry.id} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="font-semibold text-gray-900 dark:text-white">{entry.title || 'Untitled'}</h3>
                    {entry.mood && <span className="text-sm text-gray-500">🎭 {entry.mood}</span>}
                  </div>
                  <p className="text-gray-600 dark:text-gray-300 line-clamp-3">{entry.content}</p>
                  {entry.created_at && (
                    <p className="text-xs text-gray-400 mt-3">{new Date(entry.created_at).toLocaleDateString()}</p>
                  )}
                </div>
              ))}
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  );
}
