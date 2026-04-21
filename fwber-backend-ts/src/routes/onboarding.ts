import { Router } from 'express';
import prisma from '../lib/prisma.js';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// POST /api/onboarding/complete - Mark onboarding as completed
router.post('/complete', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    await prisma.users.update({
      where: { id: userId },
      data: { onboarding_completed_at: new Date() },
    });
    res.json({ message: 'Onboarding completed' });
  } catch (error: any) {
    console.error('[POST /api/onboarding/complete]', error.message);
    res.status(500).json({ message: error.message || 'Failed to complete onboarding' });
  }
});

export default router;
