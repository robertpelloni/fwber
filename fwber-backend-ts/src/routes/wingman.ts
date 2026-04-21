import { Router } from 'express';
import type { Request, Response } from 'express';

const router = Router();

// GET /api/wingman/date-ideas/general
router.get('/date-ideas/general', (_req: Request, res: Response) => {
  res.json({
    ideas: [
      { id: 1, title: 'Coffee & Conversation', category: 'casual', description: 'A relaxed coffee date at a local café.' },
      { id: 2, title: 'Park Walk', category: 'outdoor', description: 'A scenic walk through a nearby park.' },
      { id: 3, title: 'Museum Visit', category: 'culture', description: 'Explore a local museum together.' },
      { id: 4, title: 'Cooking Class', category: 'activity', description: 'Learn to cook a new dish together.' },
      { id: 5, title: 'Live Music', category: 'entertainment', description: 'Check out a local live music venue.' },
    ]
  });
});

// GET /api/wingman/date-ideas/personalized
router.get('/date-ideas/personalized', (_req: Request, res: Response) => {
  res.json({ ideas: [] });
});

// POST /api/wingman/chat
router.post('/chat', (_req: Request, res: Response) => {
  res.json({ reply: 'Hey there! I\'m your wingman. How can I help you today?' });
});

export default router;
