import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/scrapbook
router.get('/', (_req, res) => {
  res.json({ entries: [], total: 0 });
});

// POST /api/scrapbook
router.post('/', (_req, res) => {
  res.json({ success: true, entry: { id: Date.now(), ..._req.body } });
});

export default router;
