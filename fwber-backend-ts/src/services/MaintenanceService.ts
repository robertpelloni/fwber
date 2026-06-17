import prisma from '../lib/prisma.js';
import { AutonomousService } from './AutonomousService.js';
import { SentimentAnalysisService } from './SentimentAnalysisService.js';
import { PromotionService } from './PromotionService.js';
import { ActivityNotificationService } from './ActivityNotificationService.js';

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

      // 4. Process Automated Vibe Nudges
      await this.processVibeNudges();

      // 5. Log the maintenance task
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
   * Autonomously triggers merchant promotions when neighborhood vibes align.
   */
  private static async processVibeNudges() {
    try {
      // 1. Get all verified merchants with active promotions
      const merchants = await prisma.merchant_profiles.findMany({
        where: { verification_status: 'verified' },
        include: { users: { select: { token_balance: true } } }
      });

      for (const merchant of merchants) {
        // Skip if balance is too low for a nudge (e.g. 50 FWB)
        if (Number(merchant.users.token_balance) < 50) continue;

        // 2. Detect live vibe near merchant (if location exists)
        if (!merchant.address) continue;
        // In a real app we'd geocode or use lat/lng from profile.
        // For now, assume a central hub location for the hub-city.
        const lat = 42.33;
        const lng = -83.05;

        const analysis = await PromotionService.getVibeMatchedPromotions(lat, lng);

        // 3. Check for high affinity matches
        const highAffinity = analysis.matched_promotions.filter(p => p.vibe_affinity > 0.8);

        if (highAffinity.length > 0) {
          // Find nearby users who haven't been nudged in 12h
          const twelveHoursAgo = new Date(Date.now() - 12 * 60 * 60 * 1000);
          const nearby = await prisma.user_locations.findMany({
            where: {
              latitude: { gte: lat - 0.015, lte: lat + 0.015 },
              longitude: { gte: lng - 0.015, lte: lng + 0.015 },
              updated_at: { gte: new Date(Date.now() - 60 * 60 * 1000) } // Active recently
            },
            take: 20
          });

          for (const loc of nearby) {
            // Check last nudge
            const recentNudge = await prisma.notifications.findFirst({
              where: {
                user_id: loc.user_id,
                type: 'merchant_broadcast',
                created_at: { gte: twelveHoursAgo }
              }
            });

            if (!recentNudge) {
              await ActivityNotificationService.notify(
                loc.user_id,
                'Vibe Match Detected',
                `The neighborhood is feeling ${analysis.current_vibe}. ${merchant.business_name} has a deal for you!`,
                { type: 'merchant_broadcast', vibe: analysis.current_vibe, merchant_id: merchant.id.toString() }
              );

              await AutonomousService.logAction('Automated Vibe Nudge', 'Completed', {
                userId: loc.user_id.toString(),
                merchantId: merchant.id.toString(),
                vibe: analysis.current_vibe
              });
            }
          }
        }
      }
    } catch (err: any) {
      console.error('[MaintenanceService] Vibe nudging failed:', err.message);
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
