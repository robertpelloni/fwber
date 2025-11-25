'use client';

import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/lib/auth-context';

interface RateLimitConfig {
  capacity: number;
  refill_rate: number;
  cost_per_request: number;
  burst_allowance: number;
}

interface ActionStats {
  active_buckets: number;
  config: RateLimitConfig;
}

interface RateLimitStatsData {
  total_keys: number;
  timeframe: string;
  actions: Record<string, ActionStats>;
  timestamp?: string;
}

export default function RateLimitStats() {
  const { token } = useAuth();
  const [stats, setStats] = useState<RateLimitStatsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [timeframe, setTimeframe] = useState('1h');

  const fetchStats = useCallback(async () => {
    if (!token) return;
    
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch(`/api/rate-limits/stats/${timeframe}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      if (response.ok) {
        const data = await response.json();
        setStats(data.statistics); // The controller wraps it in 'statistics' key
      } else {
        setError('Failed to fetch rate limit statistics');
      }
    } catch (err) {
      console.error('Error fetching rate limit stats:', err);
      setError('An error occurred while fetching statistics');
    } finally {
      setLoading(false);
    }
  }, [token, timeframe]);

  useEffect(() => {
    fetchStats();
    const interval = setInterval(fetchStats, 60000); // Refresh every minute
    return () => clearInterval(interval);
  }, [fetchStats]);

  if (loading && !stats) {
    return (
      <div className="bg-white rounded-lg shadow-sm p-6 animate-pulse">
        <div className="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div className="space-y-3">
          <div className="h-4 bg-gray-200 rounded w-full"></div>
          <div className="h-4 bg-gray-200 rounded w-full"></div>
          <div className="h-4 bg-gray-200 rounded w-full"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="text-red-600 flex items-center space-x-2">
          <span>⚠️</span>
          <span>{error}</span>
        </div>
        <button 
          onClick={fetchStats}
          className="mt-4 text-sm text-blue-600 hover:underline"
        >
          Retry
        </button>
      </div>
    );
  }

  if (!stats) return null;

  return (
    <div className="bg-white rounded-lg shadow-sm p-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-xl font-semibold text-gray-900">🚦 Rate Limit Statistics</h2>
          <p className="text-sm text-gray-500">System-wide rate limiting metrics</p>
        </div>
        <div className="flex items-center space-x-2">
          <select
            aria-label="Select timeframe"
            value={timeframe}
            onChange={(e) => setTimeframe(e.target.value)}
            className="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="1h">Last Hour</option>
            <option value="24h">Last 24 Hours</option>
          </select>
          <button
            onClick={fetchStats}
            className="p-2 text-gray-400 hover:text-gray-600"
            title="Refresh"
          >
            🔄
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-blue-50 rounded-lg p-4">
          <p className="text-sm text-blue-600 font-medium">Total Active Buckets</p>
          <p className="text-2xl font-bold text-blue-900">{stats.total_keys}</p>
          <p className="text-xs text-blue-500 mt-1">Users currently tracked</p>
        </div>
        <div className="bg-purple-50 rounded-lg p-4">
          <p className="text-sm text-purple-600 font-medium">Monitored Actions</p>
          <p className="text-2xl font-bold text-purple-900">{Object.keys(stats.actions).length}</p>
          <p className="text-xs text-purple-500 mt-1">Distinct action types</p>
        </div>
        <div className="bg-green-50 rounded-lg p-4">
          <p className="text-sm text-green-600 font-medium">System Status</p>
          <p className="text-2xl font-bold text-green-900">Healthy</p>
          <p className="text-xs text-green-500 mt-1">Rate limiting active</p>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Users</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refill Rate</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Burst</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {Object.entries(stats.actions).map(([action, data]) => (
              <tr key={action}>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {action}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {data.active_buckets}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {data.config.capacity} reqs
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {data.config.refill_rate}/sec
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {data.config.burst_allowance}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
