'use client';

import { useSlowRequestStats } from '@/lib/hooks/use-admin-analytics';
import { formatDistanceToNow } from 'date-fns';

export default function SlowRequestStatsTable() {
  const { data: stats, isLoading, error } = useSlowRequestStats();

  if (isLoading) {
    return (
      <div className="rounded-lg bg-white p-6 shadow-sm">
        <h2 className="text-xl font-semibold text-gray-900">📊 Slow Request Stats</h2>
        <div className="mt-4 space-y-2">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-12 animate-pulse rounded bg-gray-100" />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-lg bg-white p-6 shadow-sm">
        <h2 className="text-xl font-semibold text-gray-900">📊 Slow Request Stats</h2>
        <p className="mt-2 text-sm text-red-600">Failed to load slow request stats.</p>
      </div>
    );
  }

  if (!stats || stats.length === 0) {
    return (
      <div className="rounded-lg bg-white p-6 shadow-sm">
        <h2 className="text-xl font-semibold text-gray-900">📊 Slow Request Stats</h2>
        <p className="mt-2 text-sm text-gray-500">No aggregated stats available.</p>
      </div>
    );
  }

  return (
    <div className="rounded-lg bg-white p-6 shadow-sm">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-gray-900">📊 Slow Request Stats</h2>
        <span className="text-xs text-gray-500">Aggregated by Route</span>
      </div>
      <div className="mt-4 overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route / Action</th>
              <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
              <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
              <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Max Duration</th>
              <th className="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200 bg-white">
            {stats.map((stat, index) => (
              <tr key={index} className="hover:bg-gray-50">
                <td className="px-3 py-2 text-sm text-gray-900">
                  <div className="font-medium">{stat.route_name || 'Unknown Route'}</div>
                  <div className="text-xs text-gray-500">{stat.action || 'Unknown Action'}</div>
                </td>
                <td className="px-3 py-2 whitespace-nowrap text-right text-sm text-gray-900">
                  {stat.count}
                </td>
                <td className="px-3 py-2 whitespace-nowrap text-right text-sm text-gray-900 font-mono">
                  {(stat.avg_duration / 1000).toFixed(2)}s
                </td>
                <td className="px-3 py-2 whitespace-nowrap text-right text-sm text-gray-900 font-mono">
                  {(stat.max_duration / 1000).toFixed(2)}s
                </td>
                <td className="px-3 py-2 whitespace-nowrap text-right text-sm text-gray-500">
                  {formatDistanceToNow(new Date(stat.last_occurrence), { addSuffix: true })}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
