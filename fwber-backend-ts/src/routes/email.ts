import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { sendVerificationEmail } from '../lib/email.js';
import crypto from 'crypto';

const router = Router();

/**
 * POST /api/email/verification-notification
 * Resend the verification email to the current user
 */
router.post('/verification-notification', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId }
    });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (user.email_verified_at) {
      return res.status(400).json({ message: 'Email already verified' });
    }

    // Generate or use existing verification token logic
    // For now, we'll generate a random token and send it
    const token = crypto.randomBytes(32).toString('hex');
    
    // In a real implementation, you would store this token in the DB with an expiry
    // await prisma.verification_tokens.upsert(...)

    const sent = await sendVerificationEmail(user.email!, token);

    if (sent) {
      res.json({ message: 'Verification link sent' });
    } else {
      res.status(500).json({ message: 'Failed to send verification email' });
    }
  } catch (error: any) {
    console.error('[EmailRoute] Verification error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * POST /api/email/verify
 * Verify an email address with a token
 */
router.post('/verify', async (req, res) => {
    const { token } = req.body;
    if (!token) return res.status(400).json({ message: 'Token is required' });
    
    // Logic to verify token against database and update users.email_verified_at
    // For now, we stub a success
    res.json({ success: true, message: 'Email verified' });
});

export default router;
