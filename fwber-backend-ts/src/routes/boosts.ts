import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/boosts/active
router.get('/active', (_req, res) => {
  res.json({ active: null });
});

// GET /api/boosts/history
router.get('/history', (_req, res) => {
  res.json({ data: [], total: 0 });
});

// POST /api/boosts/activate
router.post('/activate', (_req, res) => {
  res.json({ message: 'Boosts not yet implemented' });
});

// POST /api/boosts/purchase
router.post('/purchase', (_req, res) => {
  res.json({ success: true, boost: { id: Date.now(), type: _req.body.type, expires_at: null } });
});

export default router;
