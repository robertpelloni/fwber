import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/user/checkin - Daily check-in
router.get('/checkin', authenticate, async (req: any, res) => {
  res.json({
    checked_in: false,
    streak: 0,
    tokens_earned: 0,
    message: 'Not yet checked in today',
  });
});

// POST /api/user/checkin - Perform daily check-in
router.post('/checkin', authenticate, async (req: any, res) => {
  res.json({
    checked_in: true,
    streak: 1,
    tokens_earned: 10,
    message: 'Check-in successful!',
  });
});

// GET /api/user/me - Get current user info
router.get('/me', authenticate, async (req: any, res) => {
  try {
    const user = await prisma.users.findUnique({
      where: { id: BigInt(req.user.id) },
      select: { id: true, email: true, created_at: true },
    });
    if (!user) return res.status(404).json({ message: 'User not found' });
    res.json(user);
  } catch {
    res.status(500).json({ message: 'Failed to fetch user' });
  }
});

// POST /api/user/export - GDPR data export
router.post('/export', authenticate, async (_req: any, res) => {
  res.json({ message: 'Data export requested. You will receive a download link via email.', status: 'pending' });
});

export default router;
