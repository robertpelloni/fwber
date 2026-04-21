import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.post('/generate-link', authenticate, async (req: any, res) => {
  const code = Math.random().toString(36).substring(2, 10);
  res.json({ success: true, link: `https://www.fwber.me/vouch/${code}`, code });
});

router.get('/validate/:code', authenticate, async (req: any, res) => {
  res.json({ valid: false });
});

export default router;
