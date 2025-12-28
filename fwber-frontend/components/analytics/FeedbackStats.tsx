import type { PlatformAnalyticsFeedbackStats } from '@/lib/api/types';

interface FeedbackStatsProps {
  stats: PlatformAnalyticsFeedbackStats;
}

export default function FeedbackStats({ stats }: FeedbackStatsProps) {
  const total = stats.total || 1; // Avoid division by zero
  
  const getPercentage = (value: number) => Math.round((value / total) * 100);

  return (
    <div className="rounded-lg bg-white p-6 shadow-sm">
      <h2 className="text-xl font-semibold text-gray-900">💬 Feedback Insights</h2>
      
      <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        {/* Sentiment Analysis */}
        <div>
          <h3 className="text-sm font-medium text-gray-500">Sentiment Analysis</h3>
          <div className="mt-4 flex h-4 w-full overflow-hidden rounded-full bg-gray-100">
            <div 
              className="bg-green-500" 
              style={{ width: `${getPercentage(stats.sentiment.positive)}%` }}
              title={`Positive: ${stats.sentiment.positive}`}
            />
            <div 
              className="bg-gray-400" 
              style={{ width: `${getPercentage(stats.sentiment.neutral)}%` }}
              title={`Neutral: ${stats.sentiment.neutral}`}
            />
            <div 
              className="bg-red-500" 
              style={{ width: `${getPercentage(stats.sentiment.negative)}%` }}
              title={`Negative: ${stats.sentiment.negative}`}
            />
          </div>
          <div className="mt-3 flex justify-between text-xs text-gray-600">
            <div className="flex items-center gap-1">
              <div className="h-2 w-2 rounded-full bg-green-500" />
              <span>Positive ({getPercentage(stats.sentiment.positive)}%)</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="h-2 w-2 rounded-full bg-gray-400" />
              <span>Neutral ({getPercentage(stats.sentiment.neutral)}%)</span>
            </div>
            <div className="flex items-center gap-1">
              <div className="h-2 w-2 rounded-full bg-red-500" />
              <span>Negative ({getPercentage(stats.sentiment.negative)}%)</span>
            </div>
          </div>
        </div>

        {/* Top Categories */}
        <div>
          <h3 className="text-sm font-medium text-gray-500">Top Categories</h3>
          <div className="mt-4 space-y-3">
            {stats.top_categories.length === 0 ? (
              <p className="text-sm text-gray-400">No feedback categories yet.</p>
            ) : (
              stats.top_categories.map((item) => (
                <div key={item.category} className="flex items-center justify-between">
                  <span className="text-sm text-gray-700 capitalize">{item.category}</span>
                  <div className="flex items-center gap-2">
                    <div className="h-1.5 w-16 rounded-full bg-gray-100">
                      <div 
                        className="h-1.5 rounded-full bg-blue-500"
                        style={{ width: `${(item.count / total) * 100}%` }}
                      />
                    </div>
                    <span className="text-xs font-medium text-gray-900">{item.count}</span>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      <div className="mt-6 border-t border-gray-100 pt-4">
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-500">Total feedback received</p>
          <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
        </div>
      </div>
    </div>
  );
}
