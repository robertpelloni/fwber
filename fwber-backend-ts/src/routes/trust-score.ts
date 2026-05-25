import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/trust-score — current user's trust score
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    let score = await prisma.trust_scores.findUnique({
      where: { user_id: userId },
    });

    if (!score) {
      // Compute on-the-fly if not in table
      const user = await prisma.users.findUnique({
        where: { id: userId },
        select: { email_verified_at: true },
      });
      const profile = await prisma.user_profiles.findFirst({
        where: { user_id: userId },
        select: { is_verified: true, is_id_verified: true },
      });
      const photoCount = await prisma.photos.count({ where: { user_id: userId } });
      const msgCount = await prisma.messages.count({ where: { sender_id: userId } });

      let computed = 50;
      const factors: Record<string, any> = {};
      if (user?.email_verified_at) { computed += 10; factors.email_verified = true; } else { factors.email_verified = false; }
      if (profile?.is_verified) { computed += 10; factors.profile_verified = true; } else { factors.profile_verified = false; }
      if (profile?.is_id_verified) { computed += 15; factors.id_verified = true; } else { factors.id_verified = false; }
      computed += Math.min(photoCount * 2, 10);
      computed += Math.min(msgCount, 5);
      factors.photo_count = photoCount;
      factors.message_count = msgCount;

      const level = computed >= 85 ? 'gold' : computed >= 70 ? 'silver' : 'bronze';

      try {
        score = await prisma.trust_scores.create({
          data: {
            user_id: userId,
            score: computed,
            level,
            factors,
          },
        });
      } catch (_) {
        // If create fails, return computed values anyway
      }

      return res.json({
        user_id: Number(userId),
        score: computed,
        level,
        max_score: 100,
        factors,
        next_level: level === 'bronze' ? 'silver' : level === 'silver' ? 'gold' : null,
        next_level_requirements: level === 'bronze'
          ? 'Verify email (+10) and get profile verified (+10) to reach silver'
          : level === 'silver'
          ? 'Get ID verified (+15) to reach gold'
          : null,
      });
    }

    const factors = (score.factors || {}) as Record<string, any>;
    const scoreNum = Number(score.score);

    res.json({
      user_id: Number(userId),
      score: scoreNum,
      level: score.level,
      max_score: 100,
      factors,
      next_level: score.level === 'bronze' ? 'silver' : score.level === 'silver' ? 'gold' : null,
      next_level_requirements: score.level === 'bronze'
        ? 'Verify email (+10) and get profile verified (+10) to reach silver'
        : score.level === 'silver'
        ? 'Get ID verified (+15) to reach gold'
        : null,
    });
  } catch (err: any) {
    console.error('[TrustScore] Error:', err.message);
    res.json({ user_id: 0, score: 0, level: 'bronze', max_score: 100, factors: {} });
  }
});

// GET /api/trust-score/:userId — get another user's trust score (public)
router.get('/:userId', authenticate, async (req: any, res) => {
  try {
    const targetId = BigInt(req.params.userId);
    const score = await prisma.trust_scores.findUnique({
      where: { user_id: targetId },
    });

    if (!score) {
      return res.json({ user_id: Number(targetId), score: 0, level: 'bronze', max_score: 100 });
    }

    res.json({
      user_id: Number(targetId),
      score: Number(score.score),
      level: score.level,
      max_score: 100,
    });
  } catch (err: any) {
    res.json({ user_id: 0, score: 0, level: 'bronze', max_score: 100 });
  }
});

export default router;
