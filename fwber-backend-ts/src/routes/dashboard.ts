import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// All routes require authentication
router.use(authenticate);

// GET /api/dashboard/stats
router.get('/stats', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const [
      user,
      totalMatches,
      pendingMatches,
      acceptedMatches,
      profileViews,
      conversations,
    ] = await Promise.all([
      prisma.users.findUnique({ where: { id: userId } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }] } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }], status: 'pending' } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }], status: 'accepted' } }),
      prisma.profile_views.count({ where: { viewed_user_id: userId } }).catch(() => 0),
      prisma.chatrooms.count({ where: { participants: { some: {} } } }).catch(() => 0),
    ]);

    const daysActive = user?.created_at
      ? Math.max(1, Math.floor((Date.now() - new Date(user.created_at as any).getTime()) / 86400000))
      : 1;

    res.json({
      total_matches: totalMatches,
      pending_matches: pendingMatches,
      accepted_matches: acceptedMatches,
      conversations,
      profile_views: typeof profileViews === 'number' ? profileViews : 0,
      today_views: 0,
      match_score_avg: 50,
      response_rate: 0,
      days_active: daysActive,
      last_login: user?.last_seen_at?.toISOString() || new Date().toISOString(),
      current_streak: user?.current_streak || 0,
      streak_just_updated: false,
      reverb_healthy: true,
      token_balance: Number(user?.token_balance || 0),
    });
  } catch (error: any) {
    console.error('[Dashboard] Stats error:', error.message);
    res.json({
      total_matches: 0, pending_matches: 0, accepted_matches: 0,
      conversations: 0, profile_views: 0, today_views: 0,
      match_score_avg: 0, response_rate: 0, days_active: 1,
      last_login: new Date().toISOString(), current_streak: 0,
      streak_just_updated: false, reverb_healthy: true, token_balance: 0,
    });
  }
});

// GET /api/dashboard/activity?limit=8
router.get('/activity', async (req: any, res) => {
  const limit = parseInt(req.query.limit as string) || 8;
  res.json([]);
});

export default router;
