import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/feedback — list user's feedback submissions
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const feedback = await prisma.feedback.findMany({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' },
      take: 20,
    });

    res.json(feedback.map((f: any) => ({
      id: Number(f.id),
      category: f.category,
      message: f.message,
      page_url: f.page_url,
      status: f.status,
      sentiment: f.sentiment,
      created_at: f.created_at?.toISOString(),
    })));
  } catch (error: any) {
    console.error('[Feedback] List error:', error.message);
    res.json([]);
  }
});

// POST /api/feedback — submit feedback
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { category, message, content, type, page_url, metadata } = req.body;

    const feedbackMessage = message || content || '';
    if (!feedbackMessage) {
      return res.status(400).json({ message: 'Message is required' });
    }

    const feedback = await prisma.feedback.create({
      data: {
        user_id: userId,
        category: category || type || 'general',
        message: feedbackMessage,
        page_url: page_url || null,
        metadata: metadata || null,
        status: 'new',
      },
    });

    res.json({ success: true, id: Number(feedback.id) });
  } catch (error: any) {
    console.error('[Feedback] Create error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to submit feedback' });
  }
});

export default router;
