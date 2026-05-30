import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Helper: serialize BigInt
function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (typeof obj === 'object') {
    if (obj instanceof Date) return obj.toISOString();
    const out: any = {};
    for (const key of Object.keys(obj)) {
      out[key] = serialize(obj[key]);
    }
    return out;
  }
  return obj;
}

// All routes require authentication and moderator role
router.use(authenticate);

// GET /api/monitoring/autonomous - Status of autonomous execution protocol
router.get('/autonomous', async (req: any, res) => {
  try {
    if (!req.user.is_moderator) {
      return res.status(403).json({ error: 'Forbidden' });
    }

    const [recentActions, settings] = await Promise.all([
      (prisma as any).autonomous_actions.findMany({
        orderBy: { created_at: 'desc' },
        take: 10
      }),
      (prisma as any).autonomous_settings.findMany()
    ]);

    // Ensure we have some default settings if none exist

import { Router, type Response } from 'express';
import { authenticate, type AuthRequest } from '../middleware/auth.js';
import prisma, { serialize } from '../lib/prisma.js';
import { ProtocolVerificationService } from '../services/ProtocolVerificationService.js';

/**
 * GET /api/monitoring/autonomous
 */
export const getAutonomousStatus = async (req: AuthRequest, res: Response) => {
    if (!req.user?.is_moderator) {
    }

    const now = new Date();
    const twentyFourHoursAgo = new Date(now.getTime() - 24 * 60 * 60 * 1000);

    const [recentActions, settings, dailyStats] = await Promise.all([
      prisma.autonomous_actions.findMany({
        take: 15
      prisma.autonomous_settings.findMany(),
      prisma.autonomous_actions.groupBy({
        by: ['status'],
        _count: {
          _all: true
        },
        where: {
          created_at: { gte: twentyFourHoursAgo }
        }
      })

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

    const lastActionAt = recentActions.length > 0 ? recentActions[0].created_at : new Date();

    const protocolStatus = {
      is_active: true,
      current_loop: recentActions.length > 0 ? 'Monitoring' : 'Idle',
      last_action_at: lastActionAt,
      tasks_completed_today: recentActions.length,
      success_rate: 100, // Placeholder
      system_integrity: 'Optimal',

    const lastActionAt = recentActions.length > 0 ? recentActions[0]?.created_at || new Date() : new Date();

      current_loop: started > completed + failed ? 'Executing' : 'Monitoring',
      tasks_completed_today: tasksCompletedToday,
      success_rate: successRate,
      system_integrity: successRate > 95 ? 'Optimal' : (successRate > 80 ? 'Stable' : 'Degraded'),
      metrics: {
        daily_started: started,
        daily_completed: completed,
        daily_failed: failed
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
});

// POST /api/monitoring/adjust - Update automated adjustment settings
router.post('/adjust', async (req: any, res) => {
  try {
    if (!req.user.is_moderator) {

};

/**
 * POST /api/monitoring/adjust
 */
export const updateAdjustment = async (req: AuthRequest, res: Response) => {
    if (!req.user?.is_moderator) {
      return res.status(403).json({ error: 'Forbidden' });
    }

    const { key, enabled } = req.body;

    await (prisma as any).autonomous_settings.upsert({

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
});

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
