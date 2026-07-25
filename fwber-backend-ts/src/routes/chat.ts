import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { MatchingHeuristicService } from '../services/MatchingHeuristicService.js';

const router = Router();
const matchingService = new MatchingHeuristicService();

router.use(authenticate);

// POST /api/chat/aura - Create or fetch an aura-matched direct conversation
router.post('/aura', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { targetUserId } = req.body;

    if (!targetUserId) {
        return res.status(400).json({ error: 'targetUserId is required' });
    }

    const targetId = BigInt(targetUserId);

    // Get current emotions
    const [p1, p2] = await Promise.all([
        prisma.user_profiles.findFirst({ where: { user_id: userId } }),
        prisma.user_profiles.findFirst({ where: { user_id: targetId } })
    ]);

    const vibe = await matchingService.calculateConversationVibe(userId, targetId);
    const auraMatch = await matchingService.calculateAuraCompatibility(userId, targetId);

    // Broadcast the room match info for websockets (handled on frontend via useSocketLogic)

    res.status(200).json({
      success: true,
      roomId: `aura_${userId}_${targetId}`,
      vibe,
      compatibility_score: auraMatch.score,
      mood: auraMatch.mood,
      emotions: {
          user: p1?.current_emotion || 'neutral',
          target: p2?.current_emotion || 'neutral'
      }
    });
  } catch (err: any) {
    console.error('[Chat] Create Aura Chat error:', err.message);
    res.status(500).json({ error: 'Failed to create aura matched chat' });
  }
});

export default router;
