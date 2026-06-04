import prisma from '../lib/prisma.js';

export interface VerificationResult {
  passed: boolean;
  score: number; // 0-100
  anomalies: {
    type: string;
    description: string;
    severity: 'low' | 'medium' | 'high';
  }[];
  summary: {
    totalActions: number;
    danglingTasks: number;
    flappingSettings: number;
  };
}

export class ProtocolVerificationService {
  /**
   * Audit the autonomous protocol for consistency and health.
   */
  static async verifyProtocol(): Promise<VerificationResult> {
    const anomalies: VerificationResult['anomalies'] = [];
    const now = new Date();
    const window24h = new Date(now.getTime() - 24 * 60 * 60 * 1000);

    // 1. Fetch data
    const [allActions, settingsHistory] = await Promise.all([
      prisma.autonomous_actions.findMany({
        where: { created_at: { gte: window24h } },
        orderBy: { created_at: 'asc' }
      }),
      prisma.autonomous_settings.findMany()
    ]);

    // 2. Check for "Dangling Starts"
    const taskStatusMap: Record<string, { lastStatus: string; startTime: Date }> = {};
    allActions.forEach(action => {
      if (action.status === 'Started') {
        taskStatusMap[action.task] = { lastStatus: 'Started', startTime: action.created_at! };
      } else {
        delete taskStatusMap[action.task];
      }
    });

    const danglingTasks = Object.keys(taskStatusMap).filter(task => {
        const entry = taskStatusMap[task];
        if (!entry) return false;
        const timeDiff = now.getTime() - entry.startTime.getTime();
        return timeDiff > 10 * 60 * 1000; // > 10 mins
    });

    if (danglingTasks.length > 0) {
      anomalies.push({
        type: 'Dangling Tasks',
        description: `${danglingTasks.length} tasks started but never resolved in the last 24h.`,
        severity: 'medium'
      });
    }

    // 3. Check for "High-Frequency Flapping" (Settings toggled too often)
    // Since we don't have a full history table for settings yet, we check the maintenance logs
    const maintenanceLogs = allActions.filter(a => a.task === 'System Maintenance');
    let flappingCount = 0;
    let lastStrictValue: boolean | null = null;

    maintenanceLogs.forEach(log => {
        const meta = log.metadata as any;
        if (meta && meta.strict_mode !== undefined && typeof meta.strict_mode === 'boolean') {
            if (lastStrictValue !== null && lastStrictValue !== meta.strict_mode) {
                flappingCount++;
            }
            lastStrictValue = meta.strict_mode;
        }
    });

    if (flappingCount > 5) {
      anomalies.push({
        type: 'Settings Flapping',
        description: `Strict mode toggled ${flappingCount} times in 24h. System may be unstable.`,
        severity: 'high'
      });
    }

    // 4. Calculate Score
    let score = 100;
    score -= (danglingTasks.length * 5);
    score -= (flappingCount * 10);
    score = Math.max(0, score);

    return {
      passed: score > 70,
      score,
      anomalies,
      summary: {
        totalActions: allActions.length,
        danglingTasks: danglingTasks.length,
        flappingSettings: flappingCount
      }
    };
  }
}
