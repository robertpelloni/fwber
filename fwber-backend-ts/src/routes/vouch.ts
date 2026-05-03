import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

router.post('/generate-link', authenticate, async (req: any, res) => {
  const code = Math.random().toString(36).substring(2, 10);
  res.json({ success: true, link: `https://www.fwber.me/vouch/${code}`, code });
});

router.get('/validate/:code', authenticate, async (req: any, res) => {
  try {
    const { code } = req.params;
    const user = await prisma.users.findFirst({
      where: { referral_code: code }
    });

    if (!user) {
      return res.json({ valid: false });
    }

    res.json({
      valid: true,
      user_id: Number(user.id),
      name: user.name
    });
  } catch (error) {
    res.json({ valid: false });
  }
});

router.post('/submit', authenticate, async (req: any, res) => {
  try {
    const { to_user_id, type, comment, voucher_name } = req.body;
    const from_user_id = BigInt(req.user.id);

    const vouch = await prisma.vouches.create({
      data: {
        to_user_id: BigInt(to_user_id),
        from_user_id,
        type: type || 'safe',
        comment: comment || '',
        voucher_name: voucher_name || req.user.name,
      }
    });

    res.json({ success: true, vouch_id: Number(vouch.id) });
  } catch (error: any) {
    console.error('[Vouch] Error submitting:', error.message);
    res.status(500).json({ error: 'Failed to submit vouch' });
  }
});

export default router;
