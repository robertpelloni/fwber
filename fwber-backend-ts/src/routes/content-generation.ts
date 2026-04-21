import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/content-generation/stats
router.get('/stats', (_req, res) => {
  res.json({ total_generated: 0, tokens_used: 0, remaining: 0 });
});

// GET /api/content-generation/optimization-stats
router.get('/optimization-stats', (_req, res) => {
  res.json({ optimization_score: 0, suggestions: [] });
});

export default router;
