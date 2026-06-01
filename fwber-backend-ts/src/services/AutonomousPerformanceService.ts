import prisma from '../lib/prisma.js';

export class AutonomousPerformanceService {
  /**
   * Calculate average latency for a task type in the last 24 hours.
   */
  static async getAverageLatency(taskType: string): Promise<number> {
    const window24h = new Date(Date.now() - 24 * 60 * 60 * 1000);

    const actions = await prisma.autonomous_actions.findMany({
      where: {
        task: taskType,
        status: 'Completed',
        created_at: { gte: window24h }
      }
    });

    const latencies = actions
      .map(a => (a.metadata as any)?.duration_ms)
      .filter(d => typeof d === 'number');

    if (latencies.length === 0) return 0;

    const sum = latencies.reduce((a, b) => a + b, 0);
    return Math.round((sum / latencies.length) * 100) / 100;
  }

  /**
   * Get performance summary for all tasks in the last 24 hours.
   */
  static async getPerformanceSummary() {
    const window24h = new Date(Date.now() - 24 * 60 * 60 * 1000);

    const actions = await prisma.autonomous_actions.findMany({
      where: {
        status: 'Completed',
        created_at: { gte: window24h }
      }
    });

    const summary: Record<string, { avg_ms: number; count: number }> = {};

    actions.forEach(a => {
      const duration = (a.metadata as any)?.duration_ms;
      if (typeof duration === 'number' && a.task) {
        let s = summary[a.task];
        if (!s) {
          s = { avg_ms: 0, count: 0 };
          summary[a.task] = s;
        }
        s.avg_ms = (s.avg_ms * s.count + duration) / (s.count + 1);
        s.count++;
      }
    });

    // Round averages
    Object.keys(summary).forEach(k => {
      if (summary[k]) {
        summary[k]!.avg_ms = Math.round(summary[k]!.avg_ms * 100) / 100;
      }
    });

    return summary;
  }
}
