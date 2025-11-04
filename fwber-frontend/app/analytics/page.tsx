'use client';

import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../../lib/auth-context';
import { useRouter } from 'next/navigation';

interface AnalyticsData {
  users: {
    total: number;
    active: number;
    new_today: number;
    growth_rate: number;
  };
  messages: {
    total: number;
    today: number;
    average_per_user: number;
    moderation_stats: {
      flagged: number;
      approved: number;
      rejected: number;
      pending_review: number;
    };
  };
  locations: {
    total_boards: number;
    active_areas: number;
    coverage_radius: number;
    most_active: Array<{
      name: string;
      message_count: number;
      active_users: number;
    }>;
  };
  performance: {
    api_response_time: number;
    sse_connections: number;
    cache_hit_rate: number;
    error_rate: number;
  };
  trends: {
    hourly_activity: Array<{ hour: number; messages: number; users: number }>;
    daily_activity: Array<{ date: string; messages: number; users: number }>;
    top_categories: Array<{ category: string; count: number }>;
  };
}

export default function AnalyticsPage() {
  const { user, token } = useAuth();
  const router = useRouter();
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeRange, setTimeRange] = useState('7d');
  const [refreshInterval, setRefreshInterval] = useState(30000); // 30 seconds

  const fetchAnalytics = useCallback(async () => {
    try {
      const response = await fetch(`/api/analytics?range=${timeRange}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      if (response.ok) {
        const data = await response.json();
        setAnalytics(data);
      }
    } catch (error) {
      console.error('Failed to fetch analytics:', error);
    } finally {
      setLoading(false);
    }
  }, [timeRange, token]);

  useEffect(() => {
    if (!user) {
      router.push('/login');
      return;
    }

    fetchAnalytics();

    // Set up auto-refresh
    const interval = setInterval(fetchAnalytics, refreshInterval);
    return () => clearInterval(interval);
  }, [user, router, fetchAnalytics, refreshInterval]);

  if (!user) {
    return null;
  }

  if (loading && !analytics) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading analytics...</p>
        </div>
      </div>
    );
  }

  if (!analytics) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-600 text-xl mb-4">⚠️</div>
          <p className="text-red-600 mb-4">Failed to load analytics data</p>
          <button
            onClick={fetchAnalytics}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto p-6">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">📊 Analytics Dashboard</h1>
              <p className="text-gray-600 mt-1">Real-time insights into your platform performance</p>
            </div>
            <div className="flex items-center space-x-4">
              <select
                value={timeRange}
                onChange={(e) => setTimeRange(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="1d">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 90 Days</option>
              </select>
              <button
                onClick={fetchAnalytics}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                🔄 Refresh
              </button>
            </div>
          </div>
          
          {/* Real-time indicator */}
          <div className="flex items-center space-x-2 text-sm text-gray-600">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span>Live data • Last updated: {new Date().toLocaleTimeString()}</span>
          </div>
        </div>

        {/* Key Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Total Users</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.users.total.toLocaleString()}</p>
                <p className="text-sm text-green-600">+{analytics.users.growth_rate}% this week</p>
              </div>
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <span className="text-2xl">👥</span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Active Users</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.users.active.toLocaleString()}</p>
                <p className="text-sm text-blue-600">{analytics.users.new_today} new today</p>
              </div>
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <span className="text-2xl">🟢</span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Messages Today</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.messages.today.toLocaleString()}</p>
                <p className="text-sm text-gray-600">{analytics.messages.average_per_user.toFixed(1)} per user</p>
              </div>
              <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <span className="text-2xl">💬</span>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Bulletin Boards</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.locations.total_boards}</p>
                <p className="text-sm text-gray-600">{analytics.locations.active_areas} active areas</p>
              </div>
              <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <span className="text-2xl">📍</span>
              </div>
            </div>
          </div>
        </div>

        {/* Content Moderation Stats */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">🛡️ Content Moderation</h2>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="text-center p-4 bg-red-50 rounded-lg">
              <p className="text-2xl font-bold text-red-600">{analytics.messages.moderation_stats.flagged}</p>
              <p className="text-sm text-gray-600">Flagged</p>
            </div>
            <div className="text-center p-4 bg-green-50 rounded-lg">
              <p className="text-2xl font-bold text-green-600">{analytics.messages.moderation_stats.approved}</p>
              <p className="text-sm text-gray-600">Approved</p>
            </div>
            <div className="text-center p-4 bg-red-50 rounded-lg">
              <p className="text-2xl font-bold text-red-600">{analytics.messages.moderation_stats.rejected}</p>
              <p className="text-sm text-gray-600">Rejected</p>
            </div>
            <div className="text-center p-4 bg-yellow-50 rounded-lg">
              <p className="text-2xl font-bold text-yellow-600">{analytics.messages.moderation_stats.pending_review}</p>
              <p className="text-sm text-gray-600">Pending Review</p>
            </div>
          </div>
        </div>

        {/* Performance Metrics */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">⚡ Performance Metrics</h2>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="text-center p-4 bg-blue-50 rounded-lg">
              <p className="text-2xl font-bold text-blue-600">{analytics.performance.api_response_time}ms</p>
              <p className="text-sm text-gray-600">API Response Time</p>
            </div>
            <div className="text-center p-4 bg-green-50 rounded-lg">
              <p className="text-2xl font-bold text-green-600">{analytics.performance.sse_connections}</p>
              <p className="text-sm text-gray-600">SSE Connections</p>
            </div>
            <div className="text-center p-4 bg-purple-50 rounded-lg">
              <p className="text-2xl font-bold text-purple-600">{analytics.performance.cache_hit_rate}%</p>
              <p className="text-sm text-gray-600">Cache Hit Rate</p>
            </div>
            <div className="text-center p-4 bg-red-50 rounded-lg">
              <p className="text-2xl font-bold text-red-600">{analytics.performance.error_rate}%</p>
              <p className="text-sm text-gray-600">Error Rate</p>
            </div>
          </div>
        </div>

        {/* Most Active Areas */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">🔥 Most Active Areas</h2>
          <div className="space-y-3">
            {analytics.locations.most_active.map((area, index) => (
              <div key={index} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div className="flex items-center space-x-3">
                  <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                    <span className="text-sm font-bold text-orange-600">#{index + 1}</span>
                  </div>
                  <div>
                    <p className="font-medium text-gray-900">{area.name}</p>
                    <p className="text-sm text-gray-600">{area.active_users} active users</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-bold text-gray-900">{area.message_count}</p>
                  <p className="text-sm text-gray-600">messages</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Activity Trends */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">📈 Hourly Activity</h2>
            <div className="space-y-2">
              {analytics.trends.hourly_activity.map((hour, index) => (
                <div key={index} className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">{hour.hour}:00</span>
                  <div className="flex-1 mx-4">
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-blue-600 h-2 rounded-full" 
                        style={{ width: `${(hour.messages / Math.max(...analytics.trends.hourly_activity.map(h => h.messages))) * 100}%` }}
                      ></div>
                    </div>
                  </div>
                  <span className="text-sm font-medium text-gray-900">{hour.messages}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">🏷️ Top Categories</h2>
            <div className="space-y-3">
              {analytics.trends.top_categories.map((category, index) => (
                <div key={index} className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">{category.category}</span>
                  <div className="flex items-center space-x-2">
                    <div className="w-20 bg-gray-200 rounded-full h-2">
                      <div 
                        className="bg-green-600 h-2 rounded-full" 
                        style={{ width: `${(category.count / Math.max(...analytics.trends.top_categories.map(c => c.count))) * 100}%` }}
                      ></div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{category.count}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
