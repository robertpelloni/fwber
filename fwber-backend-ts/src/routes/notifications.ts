import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/notifications/count
router.get('/count', authenticate, async (req, res) => {
  res.json({ count: 0, unread: 0 });
});

// GET /api/notifications
router.get('/', authenticate, async (req, res) => {
  res.json([]);
});

export default router;
