import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

router.use(authenticate);

// GET /api/referrals/summary
router.get('/summary', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { referral_code: true },
    });

    const totalReferrals = await prisma.users.count({
      where: { referrer_id: userId },
    });

    // Token earnings from referrals (50 tokens per referral)
    const earnedTokens = totalReferrals * 50;

    // Recent referrals
    const recentReferrals = await prisma.users.findMany({
      where: { referrer_id: userId },
      select: {
        id: true,
        name: true,
        created_at: true,
      },
      take: 20,
      orderBy: { created_at: 'desc' },
    });

    res.json({
      referral_code: user?.referral_code || '',
      total_referrals: totalReferrals,
      earned_tokens: earnedTokens,
      referrals: recentReferrals.map((r: any) => ({
        id: Number(r.id),
        name: r.name || 'Anonymous',
        joined: r.created_at,
      })),
    });
  } catch (error: any) {
    console.error('[Referrals] Summary error:', error.message);
    res.json({ referral_code: '', total_referrals: 0, earned_tokens: 0, referrals: [] });
  }
});

// GET /api/referrals/code
router.get('/code', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { referral_code: true },
    });

    // Generate a code if missing
    let code = user?.referral_code || '';
    if (!code) {
      code = Math.random().toString(36).substring(2, 8).toUpperCase();
      await prisma.users.update({
        where: { id: userId },
        data: { referral_code: code },
      });
    }

    res.json({ code });
  } catch (error: any) {
    console.error('[Referrals] Code error:', error.message);
    res.json({ code: '' });
  }
});

// POST /api/referrals/apply
router.post('/apply', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { code } = req.body || {};

    if (!code) {
      return res.status(400).json({ message: 'Referral code is required' });
    }

    // Find the referrer
    const referrer = await prisma.users.findFirst({
      where: { referral_code: code },
    });

    if (!referrer) {
      return res.status(404).json({ message: 'Invalid referral code' });
    }

    if (referrer.id === userId) {
      return res.status(400).json({ message: 'Cannot use your own referral code' });
    }

    // Check if already referred
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { referrer_id: true },
    });

    if (user?.referrer_id) {
      return res.status(400).json({ message: 'You have already used a referral code' });
    }

    // Apply referral
    await prisma.users.update({
      where: { id: userId },
      data: { referrer_id: referrer.id },
    });

    // Award referrer 50 tokens
    await prisma.users.update({
      where: { id: referrer.id },
      data: { token_balance: { increment: 50 } },
    });

    res.json({
      message: 'Referral applied successfully! Your referrer earned 50 tokens.',
      referrer_name: referrer.name,
    });
  } catch (error: any) {
    console.error('[Referrals] Apply error:', error.message);
    res.status(500).json({ message: 'Failed to apply referral code' });
  }
});

export default router;
