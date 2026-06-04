import { Router, type Response } from 'express';
import { authenticate, type AuthRequest } from '../middleware/auth.js';
import prisma, { serialize } from '../lib/prisma.js';
import { ProtocolVerificationService } from '../services/ProtocolVerificationService.js';
import { AutonomousPerformanceService } from '../services/AutonomousPerformanceService.js';

const router = Router();

/**
 * GET /api/monitoring/autonomous
 */
export const getAutonomousStatus = async (req: AuthRequest, res: Response) => {
  try {
    if (!req.user?.is_moderator) {
      return res.status(403).json({ error: 'Forbidden' });
    }

    const now = new Date();
    const twentyFourHoursAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);

    const [recentActions, settings, dailyStats, performanceSummary] = await Promise.all([
      prisma.autonomous_actions.findMany({
        orderBy: { created_at: 'desc' },
        take: 15
      }),
      prisma.autonomous_settings.findMany(),
      prisma.autonomous_actions.groupBy({
        by: ['status'],
        _count: {
          _all: true
        },
        where: {
          created_at: { gte: twentyFourHoursAgo }
        }
      }),
      AutonomousPerformanceService.getPerformanceSummary()
    ]);

    // Calculate real metrics
    const statsMap: Record<string, number> = {};
    dailyStats.forEach((s: any) => {
      statsMap[s.status] = s._count._all;
    });

    const completed = statsMap['Completed'] || 0;
    const failed = statsMap['Failed'] || 0;
    const started = statsMap['Started'] || 0;

    const totalFinished = completed + failed;
    const successRate = totalFinished > 0 ? Math.round((completed / totalFinished) * 1000) / 10 : 100;
    const tasksCompletedToday = completed;

    const defaultSettings = [
      { key: 'auto_lint_fix', label: 'Auto-Fix Lint Errors', enabled: true },
      { key: 'auto_version_bump', label: 'Auto-Increment Versions', enabled: true },
      { key: 'strict_mode', label: 'Strict Protocol Enforcement', enabled: true },
      { key: 'subagent_delegation', label: 'Allow Subagent Delegation', enabled: true },
    ];

    const mappedSettings = defaultSettings.map(ds => {
      const dbSetting = settings.find((s: any) => s.key === ds.key);
      return {
        ...ds,
        enabled: dbSetting ? dbSetting.value === 'true' : ds.enabled
      };
    });

    const lastActionAt = recentActions.length > 0 ? recentActions[0]?.created_at || new Date() : new Date();

    const protocolStatus = {
      is_active: true,
      current_loop: started > completed + failed ? 'Executing' : 'Monitoring',
      last_action_at: lastActionAt,
      tasks_completed_today: tasksCompletedToday,
      success_rate: successRate,
      system_integrity: successRate > 95 ? 'Optimal' : (successRate > 80 ? 'Stable' : 'Degraded'),
      metrics: {
        daily_started: started,
        daily_completed: completed,
        daily_failed: failed,
        performance: performanceSummary
      },
      recent_actions: recentActions.map((a: any) => ({
        id: Number(a.id),
        task: a.task,
        status: a.status,
        timestamp: a.created_at
      })),
      automated_adjustments: mappedSettings
    };

    res.json(serialize(protocolStatus));
  } catch (error: any) {
    console.error('[Monitoring] Autonomous error:', error.message);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

/**
 * POST /api/monitoring/adjust
 */
export const updateAdjustment = async (req: AuthRequest, res: Response) => {
  try {
    if (!req.user?.is_moderator) {
      return res.status(403).json({ error: 'Forbidden' });
    }

    const { key, enabled } = req.body;

    await prisma.autonomous_settings.upsert({
      where: { key },
      update: { value: String(enabled) },
      create: { key, value: String(enabled) }
    });

    res.json({ success: true, key, enabled });
  } catch (error: any) {
    console.error('[Monitoring] Adjust error:', error.message);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

/**
 * GET /api/monitoring/verify
 */
export const verifyProtocol = async (req: AuthRequest, res: Response) => {
  try {
    if (!req.user?.is_moderator) {
      return res.status(403).json({ error: 'Forbidden' });
    }

    const result = await ProtocolVerificationService.verifyProtocol();
    res.json(result);
  } catch (error: any) {
    console.error('[Monitoring] Verify error:', error.message);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

// All routes require authentication
router.use(authenticate);

router.get('/autonomous', getAutonomousStatus);
router.get('/verify', verifyProtocol);
router.post('/adjust', updateAdjustment);

export default router;
