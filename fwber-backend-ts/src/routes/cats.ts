import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/cats
router.get('/', (_req, res) => {
  res.json({ data: [] });
});

export default router;
