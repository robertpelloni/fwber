import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/share-unlocks — list share unlocks
router.get('/', (_req, res) => {
  res.json({ unlocks: [], total: 0 });
});

// POST /api/share-unlocks — create share unlock
router.post('/', (_req, res) => {
  res.json({ success: true, unlock: { id: Date.now(), ..._req.body } });
});

export default router;
