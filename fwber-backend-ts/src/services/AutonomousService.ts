import prisma from '../lib/prisma.js';

export class AutonomousService {
  /**
   * Log an action performed by the autonomous protocol.
   */
  static async logAction(task: string, status: 'Started' | 'Completed' | 'Failed', metadata?: any) {
    try {
      await prisma.autonomous_actions.create({
        data: {
          task,
          status,
          metadata: metadata || {}
        }
      });
    } catch (err: any) {
      console.error('[AutonomousService] Failed to log action:', err.message);
    }
  }

  /**
   * Check if a specific adjustment is enabled.
   */
  static async isAdjustmentEnabled(key: string): Promise<boolean> {
    try {
      const setting = await prisma.autonomous_settings.findUnique({
        where: { key }
      });
      return setting ? setting.value === 'true' : true; // Default to true
    } catch (err) {
      return true;
    }
  }
}
