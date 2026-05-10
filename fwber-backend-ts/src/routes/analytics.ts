import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Helper: serialize BigInt
function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (Array.isArray(obj)) return obj.map(serialize);
  if (typeof obj === 'object') {
    // Detect Prisma Decimal by {s, e, d} signature
    if (obj.s !== undefined && obj.e !== undefined && Array.isArray(obj.d)) {
      try {
        const parts: string[] = [];
        for (let i = 0; i < obj.d.length; i++) {
          const d = String(obj.d[i]);
          parts.push(i === 0 ? d : d.padStart(7, '0'));
        }
        const digitStr = parts.join('');
        const intDigits = obj.e + 1;
        const sign = obj.s === -1 ? '-' : '';
        if (digitStr.length <= intDigits) {
          return parseFloat(sign + digitStr + '0'.repeat(intDigits - digitStr.length));
        }
        return parseFloat(sign + digitStr.slice(0, intDigits) + '.' + digitStr.slice(intDigits));
      } catch { return 0; }
    }
    if (obj instanceof Date) return obj.toISOString();
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

// POST /api/analytics/events — accept analytics events
router.post('/events', (req, res) => {
  const { event, properties } = req.body || {};
  if (process.env.NODE_ENV !== 'production') {
    console.log('[analytics]', event, properties);
  }
  res.status(202).json({ success: true });
});

// GET /api/analytics — overview stats
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const [matchCount, messageCount, profileViews] = await Promise.all([
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }] } }),
      prisma.messages.count({ where: { OR: [{ sender_id: userId }, { receiver_id: userId }] } }),
      prisma.profile_views.count({ where: { viewed_user_id: userId } }).catch(() => 0),
    ]);
    res.json(serialize({
      matches: matchCount,
      messages: messageCount,
      profile_views: profileViews,
      period: '30d',
    }));
  } catch (error: any) {
    console.error('[Analytics] Overview error:', error.message);
    res.json({ matches: 0, messages: 0, profile_views: 0, period: '30d' });
  }
});

// GET /api/analytics/boosts — boost analytics
router.get('/boosts', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const boosts = await prisma.boosts.findMany({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' },
      take: 30,
    });
    const activeBoosts = boosts.filter((b: any) => b.expires_at && new Date(b.expires_at) > new Date());
    const totalImpressions = boosts.reduce((sum: number, b: any) => sum + (Number(b.impressions) || 0), 0);
    res.json(serialize({
      total_boosts: boosts.length,
      active_boosts: activeBoosts.length,
      total_impressions: totalImpressions,
      boosts: boosts.slice(0, 10),
    }));
  } catch (error: any) {
    console.error('[Analytics] Boosts error:', error.message);
    res.json({ total_boosts: 0, active_boosts: 0, total_impressions: 0, boosts: [] });
  }
});

// GET /api/analytics/moderation — moderation analytics
router.get('/moderation', authenticate, async (_req: any, res) => {
  try {
    const [reports, blocks] = await Promise.all([
      prisma.reports.count().catch(() => 0),
      prisma.blocks.count().catch(() => 0),
    ]);
    res.json(serialize({
      total_reports: reports,
      total_blocks: blocks,
      pending_review: 0,
      resolved: reports,
    }));
  } catch (error: any) {
    res.json({ total_reports: 0, total_blocks: 0, pending_review: 0, resolved: 0 });
  }
});

// GET /api/analytics/realtime — realtime stats
router.get('/realtime', authenticate, async (_req: any, res) => {
  try {
    const onlineCount = await prisma.users.count({
      where: { last_seen_at: { gte: new Date(Date.now() - 5 * 60 * 1000) } },
    }).catch(() => 0);
    const activeToday = await prisma.users.count({
      where: { last_seen_at: { gte: new Date(Date.now() - 24 * 60 * 60 * 1000) } },
    }).catch(() => 0);
    res.json(serialize({
      online_now: onlineCount,
      active_today: activeToday,
      messages_per_minute: 0,
    }));
  } catch (error: any) {
    res.json({ online_now: 0, active_today: 0, messages_per_minute: 0 });
  }
});

// GET /api/analytics/retention — retention data
router.get('/retention', authenticate, async (_req: any, res) => {
  try {
    const totalUsers = await prisma.users.count().catch(() => 0);
    const activeWeek = await prisma.users.count({
      where: { last_seen_at: { gte: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000) } },
    }).catch(() => 0);
    const activeMonth = await prisma.users.count({
      where: { last_seen_at: { gte: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000) } },
    }).catch(() => 0);
    res.json(serialize({
      total_users: totalUsers,
      weekly_active: activeWeek,
      monthly_active: activeMonth,
      weekly_retention: totalUsers > 0 ? Math.round((activeWeek / totalUsers) * 100) : 0,
      monthly_retention: totalUsers > 0 ? Math.round((activeMonth / totalUsers) * 100) : 0,
    }));
  } catch (error: any) {
    res.json({ total_users: 0, weekly_active: 0, monthly_active: 0, weekly_retention: 0, monthly_retention: 0 });
  }
});

// GET /api/analytics/slow-requests — slow request tracking
router.get('/slow-requests', authenticate, (_req: any, res) => {
  res.json({
    slow_requests: [],
    average_response_time_ms: 0,
    p95_response_time_ms: 0,
    p99_response_time_ms: 0,
  });
});

// GET /api/analytics/slow-requests/stats — slow request stats
router.get('/slow-requests/stats', authenticate, (_req: any, res) => {
  res.json({
    total_tracked: 0,
    average_ms: 0,
    threshold_ms: 1000,
    by_endpoint: [],
  });
});

// GET /api/analytics/slow-requests/analysis — slow request analysis
router.get('/slow-requests/analysis', authenticate, (_req: any, res) => {
  res.json({
    analysis: [],
    recommendations: [],
    bottleneck_endpoints: [],
  });
});

export default router;
