import { Router } from 'express';
import { AuthController } from '../controllers/AuthController.js';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
const authController = new AuthController();

router.post('/register', authController.register);
router.post('/login', authController.login);
router.get('/me', authenticate, authController.me);

router.get('/referral/:code', async (req, res) => {
  try {
    const { code } = req.params;
    const user = await prisma.users.findFirst({
      where: { referral_code: code },
      include: { user_profiles: true }
    });

    if (!user) {
      return res.status(404).json({ valid: false, message: 'Invalid referral code' });
    }

    const profile = Array.isArray(user.user_profiles) ? user.user_profiles[0] : user.user_profiles;

    res.json({
      valid: true,
      referrer_name: profile?.display_name || user.name || 'A user',
      referrer_avatar: profile?.avatar_url || null,
      referrer_id: Number(user.id)
    });
  } catch (error: any) {
    console.error('[Auth] Referral lookup error:', error.message);
    res.status(500).json({ valid: false });
  }
});

export default router;
