import prisma from '../lib/prisma.js';
import { AutonomousService } from './AutonomousService.js';

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

      // 3. Log the maintenance task
      await AutonomousService.logAction('System Maintenance', 'Completed', {
        failureRate,
        actionCount: actions.length
      });

    } catch (err: any) {
      console.error('[MaintenanceService] Maintenance failed:', err.message);
      await AutonomousService.logAction('System Maintenance', 'Failed', { error: err.message });
    }
  }
}
