import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/status', authenticate, async (req: any, res) => {
  res.json({ status: 'none', is_verified: false, is_id_verified: false, verification_photo_path: null });
});

router.post('/submit', authenticate, async (req: any, res) => {
  res.json({ success: true, message: 'Verification submitted' });
});

export default router;
