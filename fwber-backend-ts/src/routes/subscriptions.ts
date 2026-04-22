import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/subscriptions/history
router.get('/history', (_req, res) => {
  res.json({ data: [], total: 0 });
});

// GET /api/subscriptions/current
router.get('/current', (_req, res) => {
  res.json({ plan: 'free', status: 'active', expires_at: null });
});

// POST /api/subscriptions/checkout
router.post('/checkout', (_req, res) => {
  res.json({ message: 'Not yet implemented' });
});

// POST /api/subscriptions/cancel
router.post('/cancel', (_req, res) => {
  res.json({ message: 'Not yet implemented' });
});

export default router;
