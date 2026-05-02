import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// Stub routes for /api/burner-links
router.get('/', (_req, res) => res.json([]));
router.post('/', (_req, res) => {
  const token = Math.random().toString(36).substring(2, 15);
  res.json({
    token,
    expires_at: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
    url: `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/burner/join/${token}`,
    success: true
  });
});

export default router;
