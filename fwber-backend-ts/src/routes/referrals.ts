import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/referrals/summary
router.get('/summary', (_req, res) => {
  res.json({ referral_code: '', total_referrals: 0, earned_tokens: 0, referrals: [] });
});

// GET /api/referrals/code
router.get('/code', (_req, res) => {
  res.json({ code: '' });
});

// POST /api/referrals/apply
router.post('/apply', (_req, res) => {
  res.json({ message: 'Referral applied' });
});

export default router;
