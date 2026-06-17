import prisma from '../lib/prisma.js';
import { AutonomousService } from './AutonomousService.js';
import { SentimentAnalysisService } from './SentimentAnalysisService.js';

export class MaintenanceService {
  /**
   * Perform periodic maintenance on the autonomous protocol.
   * Evaluates failure rates and toggles strict mode if necessary.
   */
  static async performMaintenance() {
    console.log('[MaintenanceService] Starting periodic health check...');

    try {
      // 1. Calculate failure rate in the last 5 minutes
      const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);

      const actions = await prisma.autonomous_actions.findMany({
        where: {
          created_at: { gte: fiveMinutesAgo }
        }
      });

      if (actions.length === 0) {
        console.log('[MaintenanceService] No actions recorded in the last 5 minutes.');
        return;
      }

      const failures = actions.filter(a => a.status === 'Failed').length;
      const failureRate = (failures / actions.length) * 100;

      console.log(`[MaintenanceService] Actions: ${actions.length}, Failures: ${failures}, Rate: ${failureRate.toFixed(2)}%`);

      // 2. Self-Correct: Toggle Strict Mode based on failure rate
      // Thresholds: > 20% triggers strict_mode, < 5% disables it.
      if (failureRate > 20) {
        console.log('[MaintenanceService] High failure rate detected. Enabling strict_mode.');
        await prisma.autonomous_settings.upsert({
          where: { key: 'strict_mode' },
          update: { value: 'true' },
          create: { key: 'strict_mode', value: 'true' }
        });
      } else if (failureRate < 5) {
        const currentStrict = await AutonomousService.isAdjustmentEnabled('strict_mode');
        if (currentStrict) {
          console.log('[MaintenanceService] Stability restored. Disabling strict_mode.');
          await prisma.autonomous_settings.update({
            where: { key: 'strict_mode' },
            data: { value: 'false' }
          });
        }
      }

      // 3. Refresh sentiment for active users
      await this.refreshActiveUserSentiment();

      // 4. Log the maintenance task
      await AutonomousService.logAction('System Maintenance', 'Completed', {
        failureRate,
        actionCount: actions.length
      });

    } catch (err: any) {
      console.error('[MaintenanceService] Maintenance failed:', err.message);
      await AutonomousService.logAction('System Maintenance', 'Failed', { error: err.message });
    }
  }

  /**
   * Identifies active users who need a sentiment refresh and triggers analysis.
   */
  private static async refreshActiveUserSentiment() {
    try {
      const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      const fourHoursAgo = new Date(Date.now() - 4 * 60 * 60 * 1000);

      // Find users seen in the last 24h whose emotion hasn't been updated in 4h
      const usersToRefresh = await prisma.users.findMany({
        where: {
          last_seen_at: { gte: oneDayAgo },
          user_profiles: {
            some: {
              OR: [
                { emotion_updated_at: { lt: fourHoursAgo } },
                { emotion_updated_at: null }
              ]
            }
          }
        },
        select: { id: true },
        take: 5 // Limit batch size to prevent hitting AI rate limits
      });

      console.log(`[MaintenanceService] Refreshing sentiment for ${usersToRefresh.length} users.`);

      for (const user of usersToRefresh) {
        await SentimentAnalysisService.analyzeUserSentiment(user.id);
      }
    } catch (err: any) {
      console.error('[MaintenanceService] Sentiment refresh failed:', err.message);
    }
  }
}
