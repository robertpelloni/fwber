import prisma from '../lib/prisma.js';
import { AutonomousTaskExecutor } from './AutonomousTaskExecutor.js';

export class AutonomousHealer {
  /**
   * Attempt to heal the system by resetting strict mode if failure rates have stabilized.
   */
  static async healStrictMode() {
    return AutonomousTaskExecutor.execute(
      { type: 'Heal: Reset Strict Mode', impact: 'low', module: 'Maintenance' },
      async () => {
        const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);

        const actions = await prisma.autonomous_actions.findMany({
          where: { created_at: { gte: fiveMinutesAgo } }
        });

        const failures = actions.filter(a => a.status === 'Failed').length;
        const failureRate = actions.length > 0 ? (failures / actions.length) * 100 : 0;

        if (failureRate < 5) {
          await prisma.autonomous_settings.upsert({
            where: { key: 'strict_mode' },
            update: { value: 'false' },
            create: { key: 'strict_mode', value: 'false' }
          });
          return { healed: true, failureRate };
        }

        return { healed: false, failureRate };
      }
    );
  }

  /**
   * Clear old autonomous logs to maintain database performance.
   */
  static async cleanupLogs() {
    return AutonomousTaskExecutor.execute(
      { type: 'Heal: Log Cleanup', impact: 'low', module: 'Maintenance' },
      async () => {
        const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
        const result = await prisma.autonomous_actions.deleteMany({
          where: { created_at: { lt: thirtyDaysAgo } }
        });
        return { deletedCount: result.count };
      }
    );
  }
}
