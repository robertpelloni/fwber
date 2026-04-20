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

    const [tokenBalance, matchBountyCount, matchAssistCount] = await Promise.all([
      prisma.users.findUnique({ where: { id: userId }, select: { token_balance: true } }),
      prisma.match_bounties.count({ where: { user_id: userId, status: 'active' } }),
      prisma.match_assists.count({ where: { matchmaker_id: userId, status: 'matched' } }),
    ]);

    res.json({
      token_balance: Number(tokenBalance?.token_balance || 0),
      active_bounties: matchBountyCount,
      successful_matches: matchAssistCount,
      total_vouches: 0,
      trust_score: 50,
    });
  } catch (error: any) {
    console.error('[Dashboard] Stats error:', error.message);
    res.json({
      token_balance: 0,
      active_bounties: 0,
      successful_matches: 0,
      total_vouches: 0,
      trust_score: 50,
    });
  }
});

// GET /api/dashboard/activity?limit=8
router.get('/activity', async (req: any, res) => {
  const limit = parseInt(req.query.limit as string) || 8;
  res.json([]);
});

export default router;
