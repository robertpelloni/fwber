import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

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

// Default ice-breaker questions
const DEFAULT_QUESTIONS = [
  { id: 1, question: "What's the most spontaneous thing you've done this year?", category: "fun", emoji: "🎉" },
  { id: 2, question: "If you could have dinner with any historical figure, who would it be?", category: "deep", emoji: "🤔" },
  { id: 3, question: "What's your hidden talent that nobody knows about?", category: "creative", emoji: "✨" },
  { id: 4, question: "What's the best compliment you've ever received?", category: "deep", emoji: "💭" },
  { id: 5, question: "If we were stranded on a desert island, what one item would you bring?", category: "fun", emoji: "🏝️" },
  { id: 6, question: "What's a topic you could talk about for hours?", category: "deep", emoji: "📚" },
  { id: 7, question: "What's the most adventurous food you've ever tried?", category: "fun", emoji: "🍽️" },
  { id: 8, question: "What's your favorite way to spend a rainy Sunday?", category: "creative", emoji: "🌧️" },
  { id: 9, question: "What's something on your bucket list you haven't done yet?", category: "spicy", emoji: "🔥" },
  { id: 10, question: "If you could instantly master any skill, what would it be?", category: "creative", emoji: "🎯" },
];

// GET /api/ice-breakers/questions — get ice-breaker questions for a match pair
router.get('/questions', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchId = parseInt(req.query.match_id as string) || 0;

    // Check if there are existing answers for this match pair
    const existingAnswers = await prisma.$queryRaw`
      SELECT ia.*, ibq.question, ibq.category, ibq.emoji
      FROM ice_breaker_answers ia
      JOIN ice_breaker_questions ibq ON ia.question_id = ibq.id
      WHERE (ia.user_id = ${userId} AND ia.match_user_id = ${BigInt(matchId)})
         OR (ia.user_id = ${BigInt(matchId)} AND ia.match_user_id = ${userId})
      ORDER BY ia.answered_at DESC
    `.catch(() => []);

    // Map answers by question_id
    const answerMap: any = {};
    for (const a of existingAnswers as any[]) {
      const qid = Number(a.question_id);
      if (!answerMap[qid]) answerMap[qid] = {};
      if (a.user_id.toString() === userId.toString()) {
        answerMap[qid].user_answered = true;
        answerMap[qid].user_answer = a.answer;
      } else {
        answerMap[qid].match_answered = true;
        answerMap[qid].match_answer = a.answer;
        answerMap[qid].is_revealed = Number(a.is_revealed) === 1;
      }
    }

    const questions = DEFAULT_QUESTIONS.map(q => ({
      ...q,
      is_active: true,
      user_answered: answerMap[q.id]?.user_answered || false,
      user_answer: answerMap[q.id]?.user_answer || null,
      match_answered: answerMap[q.id]?.match_answered || false,
      match_answer: answerMap[q.id]?.is_revealed ? answerMap[q.id]?.match_answer : null,
      is_revealed: answerMap[q.id]?.is_revealed || false,
    }));

    const totalAnswered = questions.filter(q => q.user_answered).length;

    res.json(serialize({
      questions,
      meta: {
        total_answered: totalAnswered,
        total_questions: questions.length,
      },
    }));
  } catch (error: any) {
    console.error('[IceBreakers] Questions error:', error.message);
    // Return default questions even on error
    res.json({
      questions: DEFAULT_QUESTIONS.map(q => ({
        ...q,
        is_active: true,
        user_answered: false,
        user_answer: null,
        match_answered: false,
        match_answer: null,
        is_revealed: false,
      })),
      meta: { total_answered: 0, total_questions: DEFAULT_QUESTIONS.length },
    });
  }
});

// POST /api/ice-breakers/answer — submit an answer
router.post('/answer', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { question_id, match_id, answer } = req.body || {};

    if (!question_id || !match_id || !answer) {
      return res.status(400).json({ message: 'question_id, match_id, and answer are required' });
    }

    const questionId = Number(question_id);
    const matchUserId = BigInt(match_id);

    // Check if already answered
    const existing = await prisma.$queryRaw`
      SELECT id FROM ice_breaker_answers
      WHERE user_id = ${userId} AND question_id = ${questionId} AND match_user_id = ${matchUserId}
    `.catch(() => []);

    if (Array.isArray(existing) && existing.length > 0) {
      // Update existing answer
      await prisma.$executeRaw`
        UPDATE ice_breaker_answers SET answer = ${answer}, answered_at = NOW()
        WHERE user_id = ${userId} AND question_id = ${questionId} AND match_user_id = ${matchUserId}
      `.catch(() => {});
    } else {
      // Create new answer
      await prisma.$executeRaw`
        INSERT INTO ice_breaker_answers (user_id, question_id, match_user_id, answer, is_revealed, answered_at)
        VALUES (${userId}, ${questionId}, ${matchUserId}, ${answer}, 0, NOW())
      `.catch(() => {});
    }

    // Check if match has also answered this question (reveal if both answered)
    const matchAnswer = await prisma.$queryRaw`
      SELECT id, answer FROM ice_breaker_answers
      WHERE user_id = ${matchUserId} AND question_id = ${questionId} AND match_user_id = ${userId}
    `.catch(() => []) as any[];

    const isRevealed = matchAnswer.length > 0;
    if (isRevealed) {
      await prisma.$executeRaw`
        UPDATE ice_breaker_answers SET is_revealed = 1
        WHERE question_id = ${questionId}
        AND ((user_id = ${userId} AND match_user_id = ${matchUserId})
          OR (user_id = ${matchUserId} AND match_user_id = ${userId}))
      `.catch(() => {});
    }

    const question = DEFAULT_QUESTIONS.find(q => q.id === questionId) || {
      id: questionId, question: 'Unknown', category: 'fun', emoji: '❓'
    };

    res.json(serialize({
      answer: {
        id: 1,
        user_id: Number(userId),
        question_id: questionId,
        match_user_id: Number(matchUserId),
        answer,
      },
      is_revealed: isRevealed,
      match_answer: isRevealed && matchAnswer[0] ? matchAnswer[0].answer : null,
      question: {
        ...question,
        is_active: true,
        user_answered: true,
        user_answer: answer,
        match_answered: isRevealed,
        match_answer: isRevealed && matchAnswer[0] ? matchAnswer[0].answer : null,
        is_revealed: isRevealed,
      },
    }));
  } catch (error: any) {
    console.error('[IceBreakers] Answer error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to submit answer' });
  }
});

// GET /api/ice-breakers/answers/:matchId — get all answered cards for a match
router.get('/answers/:matchId', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchId = BigInt(req.params.matchId);

    const answers = await prisma.$queryRaw`
      SELECT ia.*, ibq.question, ibq.category, ibq.emoji
      FROM ice_breaker_answers ia
      JOIN ice_breaker_questions ibq ON ia.question_id = ibq.id
      WHERE (ia.user_id = ${userId} AND ia.match_user_id = ${matchId})
         OR (ia.user_id = ${matchId} AND ia.match_user_id = ${userId})
      ORDER BY ia.answered_at DESC
    `.catch(() => []) as any[];

    const cards = answers.map((a: any) => ({
      question_id: Number(a.question_id),
      question: a.question,
      category: a.category,
      emoji: a.emoji,
      user_answer: a.user_id.toString() === userId.toString() ? a.answer : undefined,
      match_answer: a.user_id.toString() !== userId.toString() && Number(a.is_revealed) === 1 ? a.answer : null,
      is_revealed: Number(a.is_revealed) === 1,
      answered_at: a.answered_at,
    }));

    const totalRevealed = cards.filter(c => c.is_revealed).length;

    res.json(serialize({
      cards,
      meta: {
        total_answered: cards.length,
        total_revealed: totalRevealed,
      },
    }));
  } catch (error: any) {
    console.error('[IceBreakers] Answers error:', error.message);
    res.json({ cards: [], meta: { total_answered: 0, total_revealed: 0 } });
  }
});

export default router;
