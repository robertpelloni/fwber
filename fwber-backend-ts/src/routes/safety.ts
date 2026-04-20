import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/safety/walk/active
router.get('/walk/active', authenticate, async (req, res) => {
  res.json({ active: false });
});

export default router;
