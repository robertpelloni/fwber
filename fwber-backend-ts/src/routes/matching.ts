import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/matching/questions - Get questions to answer
router.get('/questions', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const questions = await prisma.matching_questions.findMany({
      where: { is_active: true },
      include: {
        matching_options: true,
        user_matching_answers: {
          where: { user_id: userId }
        }
      },
      orderBy: { priority: 'desc' }
    });

    const serialized = questions.map(q => ({
      id: q.id.toString(),
      text: q.text,
      category: q.category,
      options: q.matching_options.map(o => ({
        id: o.id.toString(),
        text: o.text
      })),
      answer: q.user_matching_answers[0] ? {
        chosen_option_id: q.user_matching_answers[0].chosen_option_id.toString(),
        accepted_option_ids: (q.user_matching_answers[0].accepted_option_ids as string[] || []).map(id => id.toString()),
        importance: q.user_matching_answers[0].importance,
        explanation: q.user_matching_answers[0].explanation
      } : null
    }));

    res.json(serialized);
  } catch (err: any) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/aura-matches', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Find recent matches/conversations
    const matches = await prisma.matches.findMany({
      where: {
        OR: [{ user1_id: userId }, { user2_id: userId }],
        status: 'accepted'
      },
      include: {
        users_matches_user1_idTousers: { include: { user_profiles: true } },
        users_matches_user2_idTousers: { include: { user_profiles: true } }
      },
      take: 10,
      orderBy: { last_message_at: 'desc' }
    });

    const auraMatches = await Promise.all(matches.map(async (m: any) => {
        const otherUser = m.user1_id === userId ? m.users_matches_user2_idTousers : m.users_matches_user1_idTousers;
        const compatibility = await matchingService.calculateAuraCompatibility(userId, otherUser.id);

        return {
            user_id: otherUser.id.toString(),
            name: otherUser.user_profiles?.[0]?.display_name || otherUser.name,
            avatar: otherUser.user_profiles?.[0]?.avatar_url,
            emotion: otherUser.user_profiles?.[0]?.current_emotion || 'Neutral',
            aura_score: compatibility.score,
            aura_mood: compatibility.mood
        };
    }));

    res.json(auraMatches.filter(m => m.aura_score > 0.5).sort((a, b) => b.aura_score - a.aura_score));
  } catch (err: any) {
    res.status(500).json({ error: err.message });
  }
});

// POST /api/matching/answer - Submit an answer
router.post('/answer', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { question_id, chosen_option_id, accepted_option_ids, importance, explanation } = req.body;

    const answer = await prisma.user_matching_answers.upsert({
      where: {
        user_id_question_id: {
          user_id: userId,
          question_id: BigInt(question_id)
        }
      },
      update: {
        chosen_option_id: BigInt(chosen_option_id),
        accepted_option_ids: accepted_option_ids,
        importance: parseInt(importance),
        explanation: explanation
      },
      create: {
        user_id: userId,
        question_id: BigInt(question_id),
        chosen_option_id: BigInt(chosen_option_id),
        accepted_option_ids: accepted_option_ids,
        importance: parseInt(importance),
        explanation: explanation
      }
    });

    res.json({ success: true, answer_id: answer.id.toString() });
  } catch (err: any) {
    res.status(500).json({ error: err.message });
  }
});

import { MatchingHeuristicService } from '../services/MatchingHeuristicService.js';
import { NarrativeService } from '../services/NarrativeService.js';

const matchingService = new MatchingHeuristicService();
const narrativeService = new NarrativeService();

router.get('/compatibility/:matchId', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchId = BigInt(req.params.matchId);
    const score = await matchingService.calculateCompatibility(userId, matchId);
    res.json({ score: Math.round(score * 100) });
  } catch (err: any) {
    res.status(500).json({ error: err.message });
  }
});

router.get('/narrative/:matchId', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchId = BigInt(req.params.matchId);
    const report = await narrativeService.generateNarrative(userId, matchId);
    res.json({ report });
  } catch (err: any) {
    res.status(500).json({ error: err.message });
  }
});

export default router;
