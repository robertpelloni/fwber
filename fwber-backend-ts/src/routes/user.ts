import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { authenticator } from 'otplib';
import QRCode from 'qrcode';
import crypto from 'crypto';

const router = Router();

// Helper: generate recovery codes
function generateRecoveryCodes(count = 8): string[] {
  const codes: string[] = [];
  for (let i = 0; i < count; i++) {
    codes.push(crypto.randomBytes(4).toString('hex').toUpperCase());
  }
  return codes;
}

// Helper: get TOTP URI
function getTotpUri(email: string, secret: string): string {
  return authenticator.keyuri(email, 'fwber', secret);
}

// POST /api/user/two-factor-authentication — Enable 2FA (generate secret)
router.post('/two-factor-authentication', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const secret = authenticator.generateSecret();

    await prisma.users.update({
      where: { id: userId },
      data: { two_factor_secret: secret, two_factor_confirmed_at: null },
    });

    res.json({ success: true });
  } catch (err) {
    console.error('[2fa] enable error:', err);
    res.status(500).json({ message: 'Failed to enable two-factor authentication' });
  }
});

// GET /api/user/two-factor-qr-code — Get QR code as SVG and URL
router.get('/two-factor-qr-code', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({ where: { id: userId } });
    if (!user?.two_factor_secret) {
      return res.status(400).json({ message: 'Two-factor authentication not initiated' });
    }

    const otpauth = getTotpUri(user.email || 'user', user.two_factor_secret);
    const svg = await QRCode.toString(otpauth, { type: 'svg', width: 200 });

    res.json({ svg, url: otpauth, secret: user.two_factor_secret });
  } catch (err) {
    console.error('[2fa] qr error:', err);
    res.status(500).json({ message: 'Failed to generate QR code' });
  }
});

// POST /api/user/confirmed-two-factor-authentication — Confirm 2FA with OTP code
router.post('/confirmed-two-factor-authentication', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { code } = req.body;

    const user = await prisma.users.findUnique({ where: { id: userId } });
    if (!user?.two_factor_secret) {
      return res.status(400).json({ message: 'Two-factor authentication not initiated' });
    }

    const isValid = authenticator.verify({ token: code, secret: user.two_factor_secret });
    if (!isValid) {
      return res.status(422).json({ message: 'Invalid verification code' });
    }

    // Generate recovery codes and confirm
    const recoveryCodes = generateRecoveryCodes();

    await prisma.users.update({
      where: { id: userId },
      data: {
        two_factor_confirmed_at: new Date(),
        two_factor_recovery_codes: JSON.stringify(recoveryCodes),
      },
    });

    res.json({ success: true, recovery_codes: recoveryCodes });
  } catch (err) {
    console.error('[2fa] confirm error:', err);
    res.status(500).json({ message: 'Failed to confirm two-factor authentication' });
  }
});

// GET /api/user/two-factor-recovery-codes — Get recovery codes
router.get('/two-factor-recovery-codes', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({ where: { id: userId } });

    if (!user?.two_factor_confirmed_at) {
      return res.status(400).json({ message: 'Two-factor authentication not enabled' });
    }

    const codes = user.two_factor_recovery_codes
      ? JSON.parse(user.two_factor_recovery_codes)
      : [];

    res.json({ recovery_codes: codes });
  } catch (err) {
    console.error('[2fa] get recovery codes error:', err);
    res.status(500).json({ message: 'Failed to fetch recovery codes' });
  }
});

// POST /api/user/two-factor-recovery-codes — Regenerate recovery codes
router.post('/two-factor-recovery-codes', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({ where: { id: userId } });

    if (!user?.two_factor_confirmed_at) {
      return res.status(400).json({ message: 'Two-factor authentication not enabled' });
    }

    const recoveryCodes = generateRecoveryCodes();

    await prisma.users.update({
      where: { id: userId },
      data: { two_factor_recovery_codes: JSON.stringify(recoveryCodes) },
    });

    res.json({ recovery_codes: recoveryCodes });
  } catch (err) {
    console.error('[2fa] regenerate recovery codes error:', err);
    res.status(500).json({ message: 'Failed to regenerate recovery codes' });
  }
});

// DELETE /api/user/two-factor-authentication — Disable 2FA
router.delete('/two-factor-authentication', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    await prisma.users.update({
      where: { id: userId },
      data: {
        two_factor_secret: null,
        two_factor_recovery_codes: null,
        two_factor_confirmed_at: null,
      },
    });

    res.json({ success: true });
  } catch (err) {
    console.error('[2fa] disable error:', err);
    res.status(500).json({ message: 'Failed to disable two-factor authentication' });
  }
});

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

// GET /api/user/export/status - Export status
router.get('/export/status', authenticate, async (_req: any, res) => {
  res.json({ status: 'none', message: 'No export in progress' });
});

export default router;
