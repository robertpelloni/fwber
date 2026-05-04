import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { sendVerificationEmail } from '../lib/email.js';
import crypto from 'crypto';

const router = Router();

// In-memory token store (resets on server restart; for production, use DB table)
// Token -> { userId, email, expires }
const verificationTokens = new Map<string, { userId: bigint; email: string; expires: number }>();

// Clean expired tokens every 10 minutes
setInterval(() => {
  const now = Date.now();
  for (const [token, data] of verificationTokens) {
    if (data.expires < now) verificationTokens.delete(token);
  }
}, 10 * 60 * 1000);

/**
 * POST /api/email/verification-notification
 * Resend the verification email to the current user
 */
router.post('/verification-notification', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({ where: { id: userId } });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (user.email_verified_at) {
      return res.status(400).json({ message: 'Email already verified' });
    }

    // Generate verification token (24h expiry)
    const token = crypto.randomBytes(32).toString('hex');
    verificationTokens.set(token, {
      userId: user.id,
      email: user.email!,
      expires: Date.now() + 24 * 60 * 60 * 1000,
    });

    const sent = await sendVerificationEmail(user.email!, token);

    if (sent) {
      res.json({ message: 'Verification link sent' });
    } else {
      // Even if email delivery fails, return the token in dev mode
      // so the frontend can still verify (for testing/development)
      const isDev = process.env.NODE_ENV !== 'production';
      if (isDev) {
        console.log('[Email Route] Dev mode: returning token for manual verification');
        res.json({
          message: 'Email delivery failed, but verification token generated (dev mode)',
          dev_token: token,
        });
      } else {
        res.status(500).json({ message: 'Failed to send verification email' });
      }
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
  try {
    const { token } = req.body;

    if (!token) {
      return res.status(400).json({ message: 'Token is required' });
    }

    const stored = verificationTokens.get(token);

    if (!stored) {
      return res.status(400).json({ message: 'Invalid or expired verification token' });
    }

    if (stored.expires < Date.now()) {
      verificationTokens.delete(token);
      return res.status(400).json({ message: 'Verification token has expired' });
    }

    // Update the user's email_verified_at
    await prisma.users.update({
      where: { id: stored.userId },
      data: { email_verified_at: new Date() },
    });

    // Remove the used token
    verificationTokens.delete(token);

    console.log('[Email Route] Email verified for user', stored.userId.toString());

    res.json({ success: true, message: 'Email verified successfully' });
  } catch (error: any) {
    console.error('[EmailRoute] Verify error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

/**
 * GET /api/email/status
 * Check if the current user's email is verified
 */
router.get('/status', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { email: true, email_verified_at: true },
    });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    res.json({
      email: user.email,
      verified: !!user.email_verified_at,
      verified_at: user.email_verified_at,
    });
  } catch (error: any) {
    console.error('[EmailRoute] Status error:', error.message);
    res.status(500).json({ message: 'Internal Server Error' });
  }
});

export default router;
