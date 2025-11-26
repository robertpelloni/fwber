              <p className="text-2xl font-bold text-red-600">{analytics.messages.moderation_stats.rejected}</p>
              <p className="text-2xl font-bold text-red-600">{analytics.messages.moderation_stats.rejected}</p>
              <p className="text-sm text-gray-600">Rejected</p>
            </div>
            <div className="text-center p-4 bg-yellow-50 rounded-lg">
              <p className="text-2xl font-bold text-yellow-600">{analytics.messages.moderation_stats.pending_review}</p>
              <p className="text-sm text-gray-600">Pending Review</p>
            </div>
          </div>
        </div>

        {/* Rate Limit Stats */}
        <div className="mb-8">
          <RateLimitStats />
        </div>

        {/* Most Active Areas */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">🔥 Most Active Areas</h2>
          <div className="space-y-3">
            {mostActiveAreas.length === 0 ? (
              <p className="text-sm text-gray-500">No active areas detected for this range.</p>
            ) : (
              mostActiveAreas.map((area, index) => (
                <div
                  key={`${area.name}-${index}`}
                  className="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                >
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
              ))
            )}
          </div>
        </div>

        {/* Activity Trends */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">📈 Hourly Activity</h2>
            <div className="space-y-2">
              {analytics.trends.hourly_activity.map((hour, index) => (
                <div key={`hour-${hour.hour}-${index}`} className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">{hour.hour}:00</span>
                  <div className="flex-1 mx-4">
                    <div className="w-full bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-blue-600 h-2 rounded-full"
                        style={{ width: `${(hour.messages / hourlyMaxMessages) * 100}%` }}
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
                <div key={`category-${category.category}-${index}`} className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">{category.category}</span>
                  <div className="flex items-center space-x-2">
                    <div className="w-20 bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-green-600 h-2 rounded-full"
                        style={{ width: `${(category.count / topCategoryMax) * 100}%` }}
                      ></div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{category.count}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Live system signals */}
          </div>
        </div>

        {/* Activity Trends */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">📈 Hourly Activity</h2>
            <div className="space-y-2">
              {analytics.trends.hourly_activity.map((hour, index) => (
                      <div
                        className="bg-green-600 h-2 rounded-full"
                        style={{ width: `${(category.count / topCategoryMax) * 100}%` }}
                      ></div>
                      <div 
                        className="bg-blue-600 h-2 rounded-full" 
                        style={{ width: `${(hour.messages / Math.max(...analytics.trends.hourly_activity.map(h => h.messages))) * 100}%` }}
                      ></div>
                    </div>
                  </div>
                  <span className="text-sm font-medium text-gray-900">{hour.messages}</span>
                </div>

        {/* Live system signals */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-900">🔌 Live System Signals</h2>
            {realtimeQuery.isFetching && (
              <span className="text-xs text-gray-500">Refreshing…</span>
            )}
          </div>
          {realtimeMetrics ? (
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <p className="text-sm text-blue-600 font-medium">Active Connections</p>
                <p className="text-2xl font-bold text-blue-900">{realtimeMetrics.active_connections}</p>
                <p className="text-xs text-blue-500 mt-1">Current Mercure/WebSocket sessions</p>
              </div>
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <p className="text-sm text-green-600 font-medium">Messages / Minute</p>
                <p className="text-2xl font-bold text-green-900">{realtimeMetrics.messages_per_minute}</p>
                <p className="text-xs text-green-500 mt-1">Across chatrooms + DMs</p>
              </div>
              <div className="text-center p-4 bg-purple-50 rounded-lg">
                <p className="text-sm text-purple-600 font-medium">System Load</p>
                <p className="text-2xl font-bold text-purple-900">{realtimeMetrics.system_load}%</p>
                <p className="text-xs text-purple-500 mt-1">Avg server utilization</p>
              </div>
            </div>
          ) : (
            <p className="text-sm text-gray-500">Realtime metrics unavailable. Check Mercure/API health.</p>
          )}
        </div>

        {/* Moderation insights */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-10">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">🧹 Moderation Insights</h2>
          {moderationInsights ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div className="text-center p-4 bg-blue-50 rounded-lg">
                  <p className="text-sm text-blue-600 font-medium">AI Accuracy</p>
                  <p className="text-2xl font-bold text-blue-900">{moderationInsights.ai_accuracy}%</p>
                  <p className="text-xs text-blue-500 mt-1">Automated scoring confidence</p>
                </div>
                <div className="text-center p-4 bg-green-50 rounded-lg">
                  <p className="text-sm text-green-600 font-medium">Human Review Rate</p>
                  <p className="text-2xl font-bold text-green-900">{moderationInsights.human_review_rate}%</p>
                  <p className="text-xs text-green-500 mt-1">Items escalated to humans</p>
                </div>
                <div className="text-center p-4 bg-yellow-50 rounded-lg">
                  <p className="text-sm text-yellow-600 font-medium">False Positive Rate</p>
                  <p className="text-2xl font-bold text-yellow-900">{moderationInsights.false_positive_rate}%</p>
                  <p className="text-xs text-yellow-500 mt-1">Requires policy tuning</p>
                </div>
                <div className="text-center p-4 bg-purple-50 rounded-lg">
                  <p className="text-sm text-purple-600 font-medium">Avg Review Time</p>
                  <p className="text-2xl font-bold text-purple-900">{moderationInsights.average_review_time}s</p>
                  <p className="text-xs text-purple-500 mt-1">Queue processing speed</p>
                </div>
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-3">Top Flagged Categories</h3>
                <div className="space-y-3">
                  {moderationInsights.top_flagged_categories.map((item, index) => (
                    <div key={`${item.category}-${index}`} className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">{item.category}</span>
                      <span className="text-sm font-medium text-gray-900">{item.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            </>
          ) : (
            <p className="text-sm text-gray-500">Moderation insights will appear once the backend exposes data.</p>
          )}
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
