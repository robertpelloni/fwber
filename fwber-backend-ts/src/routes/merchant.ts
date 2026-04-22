import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/merchant/pulse/vibe
router.get('/pulse/vibe', (_req, res) => {
  res.json({
    vibe: 'neutral',
    score: 0.5,
    confidence: 0,
    message: 'Vibe analysis not yet available',
  });
});

export default router;
