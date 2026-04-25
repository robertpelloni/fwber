import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/moderation — moderation queue
router.get('/', (_req, res) => {
  res.json({ reports: [], total: 0 });
});

// POST /api/moderation/:id/action
router.post('/:id/action', (_req, res) => {
  res.json({ success: true });
});

export default router;
