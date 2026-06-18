import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { serialize } from '../lib/prisma.js';
import { TokenDistributionService } from '../services/TokenDistributionService.js';

const router = Router();
const tokenService = new TokenDistributionService();

// GET /api/quests/active — Get active neighborhood quests for the user
router.get('/active', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const quests = await prisma.quests.findMany({
      where: {
        is_active: true,
        expires_at: { gt: new Date() }
      },
      include: {
        user_quests: {
          where: { user_id: userId }
        }
      }
    });

    res.json(serialize(quests));
  } catch (error: any) {
    res.status(500).json({ error: 'Failed to fetch quests' });
  }
});

// POST /api/quests/:id/accept — Join a quest
router.post('/:id/accept', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const questId = BigInt(req.params.id);

    const userQuest = await prisma.user_quests.upsert({
      where: { user_id_quest_id: { user_id: userId, quest_id: questId } },
      update: {},
      create: {
        user_id: userId,
        quest_id: questId,
        status: 'active',
        progress: { current: 0, target: 3 }
      }
    });

    res.json({ success: true, userQuest: serialize(userQuest) });
  } catch (error: any) {
    res.status(500).json({ error: 'Failed to accept quest' });
  }
});

// POST /api/quests/:id/complete — Mark quest as completed
router.post('/:id/complete', authenticate, async (req: any, res) => {
    try {
      const userId = BigInt(req.user.id);
      const questId = BigInt(req.params.id);

      const uq = await prisma.user_quests.findUnique({
        where: { user_id_quest_id: { user_id: userId, quest_id: questId } },
        include: { quests: true }
      });

      if (!uq || uq.status !== 'active') return res.status(400).json({ error: 'Quest not active' });

      // Grant rewards
      await tokenService.awardTokens(
        userId,
        Number(uq.quests.token_reward),
        'quest_reward',
        `Completed Quest: ${uq.quests.title}`
      );

      const updated = await prisma.user_quests.update({
        where: { id: uq.id },
        data: {
          status: 'claimed',
          completed_at: new Date()
        }
      });

      res.json({ success: true, reward: Number(uq.quests.token_reward) });
    } catch (error: any) {
      res.status(500).json({ error: 'Failed to complete quest' });
    }
  });

export default router;
