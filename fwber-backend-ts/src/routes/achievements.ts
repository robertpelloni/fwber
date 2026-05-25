import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/achievements — list all achievements with user's earned status
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Get all achievements
    const allAchievements = await prisma.achievements.findMany({
      orderBy: { id: 'asc' },
    });

    // Get user's earned achievements
    const userAchievements = await prisma.user_achievements.findMany({
      where: { user_id: userId },
    });

    const earnedMap = new Map<string, Date>();
    for (const ua of userAchievements) {
      earnedMap.set(ua.achievement_id.toString(), ua.unlocked_at ? new Date(ua.unlocked_at) : new Date());
    }

    const achievements = allAchievements.map((a: any) => ({
      id: Number(a.id),
      slug: a.slug,
      name: a.name || a.slug?.replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()),
      description: a.description || a.name || `Achievement: ${a.slug}`,
      icon: a.icon || '🏆',
      token_reward: a.token_reward ? Number(a.token_reward) : 0,
      earned: earnedMap.has(a.id.toString()),
      earned_at: earnedMap.get(a.id.toString())?.toISOString() || null,
    }));

    res.json({
      achievements,
      total: achievements.length,
      earned: userAchievements.length,
    });
  } catch (error: any) {
    console.error('[Achievements] Error:', error.message);
    // Fallback to static data
    res.json({
      achievements: [
        { id: 1, slug: 'profile_verified', name: 'Verified Human', description: 'Complete profile verification', icon: '✅', token_reward: 50, earned: false, earned_at: null },
        { id: 2, slug: 'first_match', name: 'First Connection', description: 'Make your first match', icon: '🤝', token_reward: 10, earned: false, earned_at: null },
        { id: 3, slug: 'streak_7', name: 'Week on Fire', description: '7-day login streak', icon: '🔥', token_reward: 100, earned: false, earned_at: null },
      ],
      total: 3,
      earned: 0,
    });
  }
});

export default router;
