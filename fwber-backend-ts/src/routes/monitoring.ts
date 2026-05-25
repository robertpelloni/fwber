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
      return res.status(403).json({ error: 'Forbidden' });
    }

    const { key, enabled } = req.body;

    await (prisma as any).autonomous_settings.upsert({
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

export default router;
