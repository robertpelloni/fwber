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
