/**
 * CSV Export Utility for Analytics Dashboard
 *
 * Provides functions to convert analytics data to CSV format
 * and trigger file downloads in the browser.
 */

import type {
  PlatformAnalyticsResponse,
  ModerationInsights,
  RateLimitStatistics,
} from '@/lib/api/types';

/**
 * Convert array of objects to CSV string
 */
function objectsToCSV<T extends Record<string, unknown>>(data: T[], headers?: string[]): string {
  if (data.length === 0) return '';

  const keys = headers ?? Object.keys(data[0]);
  const headerRow = keys.join(',');

  const rows = data.map((item) =>
    keys
      .map((key) => {
        const value = item[key];
        // Escape quotes and wrap in quotes if needed
        if (value === null || value === undefined) return '';
        const strValue = String(value);
        if (strValue.includes(',') || strValue.includes('"') || strValue.includes('\n')) {
          return `"${strValue.replace(/"/g, '""')}"`;
        }
        return strValue;
      })
      .join(',')
  );

  return [headerRow, ...rows].join('\n');
}

/**
 * Trigger a file download in the browser
 */
function downloadCSV(csvContent: string, filename: string): void {
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/**
 * Export platform analytics summary to CSV
 */
export function exportPlatformAnalyticsToCSV(
  analytics: PlatformAnalyticsResponse,
  range: string
): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `analytics-summary-${range}-${timestamp}.csv`;

  // Summary metrics
  const summaryRows = [
    { metric: 'Total Users', value: analytics.users.total },
    { metric: 'Active Users', value: analytics.users.active },
    { metric: 'New Users Today', value: analytics.users.new_today },
    { metric: 'User Growth Rate', value: `${analytics.users.growth_rate.toFixed(1)}%` },
    { metric: 'Total Messages', value: analytics.messages.total },
    { metric: 'Messages Today', value: analytics.messages.today },
    { metric: 'Avg Messages per User', value: analytics.messages.average_per_user.toFixed(2) },
    { metric: 'Flagged Content', value: analytics.messages.moderation_stats.flagged },
    { metric: 'Approved Content', value: analytics.messages.moderation_stats.approved },
    { metric: 'Rejected Content', value: analytics.messages.moderation_stats.rejected },
    { metric: 'Pending Review', value: analytics.messages.moderation_stats.pending_review },
    { metric: 'Active Areas', value: analytics.locations.active_areas },
    { metric: 'Coverage Radius (km)', value: analytics.locations.coverage_radius },
    { metric: 'API Response Time (ms)', value: analytics.performance.api_response_time },
    { metric: 'SSE Connections', value: analytics.performance.sse_connections },
    { metric: 'Cache Hit Rate', value: `${analytics.performance.cache_hit_rate.toFixed(1)}%` },
    { metric: 'Error Rate', value: `${analytics.performance.error_rate.toFixed(2)}%` },
  ];

  const csvContent = objectsToCSV(summaryRows);
  downloadCSV(csvContent, filename);
}

/**
 * Export hourly activity data to CSV
 */
export function exportHourlyActivityToCSV(
  analytics: PlatformAnalyticsResponse,
  range: string
): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `hourly-activity-${range}-${timestamp}.csv`;

  const rows = analytics.trends.hourly_activity.map((entry) => ({
    hour: `${entry.hour}:00`,
    messages: entry.messages,
    users: entry.users,
  }));

  const csvContent = objectsToCSV(rows);
  downloadCSV(csvContent, filename);
}

/**
 * Export top categories to CSV
 */
export function exportTopCategoriesToCSV(
  analytics: PlatformAnalyticsResponse,
  range: string
): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `top-categories-${range}-${timestamp}.csv`;

  const csvContent = objectsToCSV(analytics.trends.top_categories);
  downloadCSV(csvContent, filename);
}

/**
 * Export most active areas to CSV
 */
export function exportActiveAreasToCSV(
  analytics: PlatformAnalyticsResponse,
  range: string
): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `active-areas-${range}-${timestamp}.csv`;

  const rows = analytics.locations.most_active.map((area) => ({
    name: area.name,
    active_users: area.active_users,
    message_count: area.message_count,
  }));

  const csvContent = objectsToCSV(rows);
  downloadCSV(csvContent, filename);
}

/**
 * Export moderation insights to CSV
 */
export function exportModerationInsightsToCSV(insights: ModerationInsights): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `moderation-insights-${timestamp}.csv`;

  const summaryRows = [
    { metric: 'AI Accuracy', value: `${insights.ai_accuracy.toFixed(1)}%` },
    { metric: 'Human Review Rate', value: `${insights.human_review_rate.toFixed(1)}%` },
    { metric: 'False Positive Rate', value: `${insights.false_positive_rate.toFixed(1)}%` },
    { metric: 'Avg Review Time (s)', value: insights.average_review_time.toFixed(1) },
  ];

  // Add categories
  insights.top_flagged_categories.forEach((cat, index) => {
    summaryRows.push({
      metric: `Top Flagged Category #${index + 1}`,
      value: `${cat.category}: ${cat.count}`,
    });
  });

  const csvContent = objectsToCSV(summaryRows);
  downloadCSV(csvContent, filename);
}

/**
 * Export rate limit statistics to CSV
 */
export function exportRateLimitStatsToCSV(stats: RateLimitStatistics): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `rate-limit-stats-${stats.timeframe}-${timestamp}.csv`;

  const rows = Object.entries(stats.actions).map(([action, data]) => ({
    action,
    active_buckets: data.active_buckets,
    capacity: data.config.capacity,
    refill_rate: data.config.refill_rate,
    cost_per_request: data.config.cost_per_request,
    burst_allowance: data.config.burst_allowance,
  }));

  // Add summary row
  rows.unshift({
    action: 'TOTAL',
    active_buckets: stats.total_keys,
    capacity: '-' as unknown as number,
    refill_rate: '-' as unknown as number,
    cost_per_request: '-' as unknown as number,
    burst_allowance: '-' as unknown as number,
  });

  const csvContent = objectsToCSV(rows);
  downloadCSV(csvContent, filename);
}

/**
 * Export all analytics data as a combined CSV
 */
export function exportAllAnalyticsToCSV(
  analytics: PlatformAnalyticsResponse,
  moderationInsights: ModerationInsights | null,
  range: string
): void {
  const timestamp = new Date().toISOString().split('T')[0];
  const filename = `full-analytics-report-${range}-${timestamp}.csv`;

  let csvContent = 'FWBer Analytics Report\n';
  csvContent += `Generated: ${new Date().toLocaleString()}\n`;
  csvContent += `Range: ${range}\n`;
  csvContent += '\n';

  // User Stats
  csvContent += '--- USER STATISTICS ---\n';
  csvContent += `Total Users,${analytics.users.total}\n`;
  csvContent += `Active Users,${analytics.users.active}\n`;
  csvContent += `New Users Today,${analytics.users.new_today}\n`;
  csvContent += `Growth Rate,${analytics.users.growth_rate.toFixed(1)}%\n`;
  csvContent += '\n';

  // Message Stats
  csvContent += '--- MESSAGE STATISTICS ---\n';
  csvContent += `Total Messages,${analytics.messages.total}\n`;
  csvContent += `Messages Today,${analytics.messages.today}\n`;
  csvContent += `Avg per User,${analytics.messages.average_per_user.toFixed(2)}\n`;
  csvContent += '\n';

  // Moderation
  csvContent += '--- MODERATION ---\n';
  csvContent += `Flagged,${analytics.messages.moderation_stats.flagged}\n`;
  csvContent += `Approved,${analytics.messages.moderation_stats.approved}\n`;
  csvContent += `Rejected,${analytics.messages.moderation_stats.rejected}\n`;
  csvContent += `Pending Review,${analytics.messages.moderation_stats.pending_review}\n`;
  csvContent += '\n';

  // Performance
  csvContent += '--- PERFORMANCE ---\n';
  csvContent += `API Response Time (ms),${analytics.performance.api_response_time}\n`;
  csvContent += `SSE Connections,${analytics.performance.sse_connections}\n`;
  csvContent += `Cache Hit Rate,${analytics.performance.cache_hit_rate.toFixed(1)}%\n`;
  csvContent += `Error Rate,${analytics.performance.error_rate.toFixed(2)}%\n`;
  csvContent += '\n';

  // Hourly Activity
  csvContent += '--- HOURLY ACTIVITY ---\n';
  csvContent += 'Hour,Messages,Users\n';
  analytics.trends.hourly_activity.forEach((entry) => {
    csvContent += `${entry.hour}:00,${entry.messages},${entry.users}\n`;
  });
  csvContent += '\n';

  // Top Categories
  csvContent += '--- TOP CATEGORIES ---\n';
  csvContent += 'Category,Count\n';
  analytics.trends.top_categories.forEach((cat) => {
    csvContent += `${cat.category},${cat.count}\n`;
  });
  csvContent += '\n';

  // Active Areas
  csvContent += '--- ACTIVE AREAS ---\n';
  csvContent += 'Area,Active Users,Messages\n';
  analytics.locations.most_active.forEach((area) => {
    csvContent += `${area.name},${area.active_users},${area.message_count}\n`;
  });
  csvContent += '\n';

  // Moderation Insights (if available)
  if (moderationInsights) {
    csvContent += '--- MODERATION INSIGHTS ---\n';
    csvContent += `AI Accuracy,${moderationInsights.ai_accuracy.toFixed(1)}%\n`;
    csvContent += `Human Review Rate,${moderationInsights.human_review_rate.toFixed(1)}%\n`;
    csvContent += `False Positive Rate,${moderationInsights.false_positive_rate.toFixed(1)}%\n`;
    csvContent += `Avg Review Time (s),${moderationInsights.average_review_time.toFixed(1)}\n`;
    csvContent += '\nTop Flagged Categories:\n';
    moderationInsights.top_flagged_categories.forEach((cat) => {
      csvContent += `${cat.category},${cat.count}\n`;
    });
  }

  downloadCSV(csvContent, filename);
}
