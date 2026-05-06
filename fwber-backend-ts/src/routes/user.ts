import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { filePathToUrl } from '../lib/photos.js';
import { authenticator } from 'otplib';
import QRCode from 'qrcode';
import crypto from 'crypto';
import { sendNotificationEmail } from '../lib/email.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';

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

// GET /api/user/checkin - Daily check-in status
router.get('/checkin', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { current_streak: true, last_daily_bonus_at: true, token_balance: true },
    });

    if (!user) {
      return res.json({ checked_in: false, streak: 0, tokens_earned: 0, message: 'User not found' });
    }

    // Check if already checked in today
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const lastBonus = user.last_daily_bonus_at ? new Date(user.last_daily_bonus_at) : null;
    const checkedIn = lastBonus ? lastBonus >= today : false;

    res.json({
      checked_in: checkedIn,
      streak: Number(user.current_streak || 0),
      tokens_earned: checkedIn ? 10 : 0,
      token_balance: Number(user.token_balance || 0),
      message: checkedIn ? 'Already checked in today!' : 'Not yet checked in today',
    });
  } catch (error: any) {
    console.error('[Checkin] Status error:', error.message);
    res.json({ checked_in: false, streak: 0, tokens_earned: 0, message: 'Error checking status' });
  }
});

// POST /api/user/checkin - Perform daily check-in
router.post('/checkin', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { current_streak: true, last_daily_bonus_at: true, token_balance: true },
    });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    // Check if already checked in today
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const lastBonus = user.last_daily_bonus_at ? new Date(user.last_daily_bonus_at) : null;

    if (lastBonus && lastBonus >= today) {
      return res.json({
        checked_in: true,
        streak: Number(user.current_streak || 0),
        tokens_earned: 0,
        token_balance: Number(user.token_balance || 0),
        message: 'Already checked in today!',
      });
    }

    // Calculate streak
    let newStreak = 1;
    if (lastBonus) {
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      if (lastBonus >= yesterday) {
        // Consecutive day
        newStreak = Number(user.current_streak || 0) + 1;
      }
    }

    // Calculate tokens based on streak
    const baseTokens = 10;
    const streakBonus = Math.min(newStreak - 1, 10) * 2; // +2 per streak day, max +20
    const totalTokens = baseTokens + streakBonus;

    // Update user and record transaction
    await prisma.users.update({
      where: { id: userId },
      data: {
        current_streak: newStreak,
        last_daily_bonus_at: new Date(),
        token_balance: { increment: totalTokens },
      },
    });

    // Record wallet transaction
    try {
      await prisma.wallet_transactions.create({
        data: {
          user_id: userId,
          amount: totalTokens,
          type: 'reward',
          description: newStreak > 1 ? `${newStreak}-day streak bonus` : 'Daily check-in reward',
          created_at: new Date(),
        },
      });
    } catch (_) {}

    // Check achievements (daily checkin, streak milestones)
    checkAndUnlockAchievements(userId).catch(() => {});

    res.json({
      checked_in: true,
      streak: newStreak,
      tokens_earned: totalTokens,
      token_balance: Number(user.token_balance || 0) + totalTokens,
      message: newStreak > 1
        ? `${newStreak}-day streak! Earned ${totalTokens} tokens!`
        : `Check-in successful! Earned ${totalTokens} tokens!`,
    });
  } catch (error: any) {
    console.error('[Checkin] Error:', error.message);
    res.status(500).json({ message: 'Failed to check in' });
  }
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


// GET /api/user/search — search users by name
router.get('/search', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const q = String(req.query.q || '').trim();
    if (!q || q.length < 2) {
      return res.json([]);
    }

    const users = await prisma.users.findMany({
      where: {
        id: { not: userId },
        name: { contains: q },
      },
      include: {
        user_profiles: {
          select: { display_name: true, avatar_url: true },
          take: 1,
        },
      },
      take: 20,
    });

    res.json(users.map((u: any) => {
      const profile = Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles;
      return {
        id: Number(u.id),
        name: u.name,
        display_name: profile?.display_name || u.name,
        avatar_url: profile?.avatar_url || null,
      };
    }));
  } catch (error: any) {
    console.error('[User] Search error:', error.message);
    res.json([]);
  }
});

// POST /api/user/export - GDPR data export
router.post('/export', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      include: {
        user_profiles: true,
        photos: true,
      }
    });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    // Fetch related data separately for resilience
    let recentMessages: any[] = [];
    let recentMatches: any[] = [];

    try {
      recentMessages = await prisma.messages.findMany({
        where: { sender_id: userId },
        take: 100,
        orderBy: { created_at: 'desc' },
        select: { content: true, created_at: true },
      });
    } catch (_) {}

    try {
      const m1 = await prisma.matches.findMany({
        where: { user1_id: userId },
        take: 25,
        orderBy: { created_at: 'desc' },
        select: { user2_id: true, status: true, created_at: true },
      });
      const m2 = await prisma.matches.findMany({
        where: { user2_id: userId },
        take: 25,
        orderBy: { created_at: 'desc' },
        select: { user1_id: true, status: true, created_at: true },
      });
      recentMatches = [
        ...m1.map((m: any) => ({ partner_id: Number(m.user2_id), date: m.created_at, status: m.status })),
        ...m2.map((m: any) => ({ partner_id: Number(m.user1_id), date: m.created_at, status: m.status })),
      ];
    } catch (_) {}

    // Structure the data for export
    const exportData = {
      account: {
        id: user.id.toString(),
        email: user.email,
        created_at: user.created_at,
        last_seen_at: user.last_seen_at
      },
      profile: user.user_profiles?.[0] || {},
      photos: user.photos.map(p => ({
        url: filePathToUrl(p.file_path),
        is_primary: p.is_primary,
        created_at: p.created_at
      })),
      recent_messages: recentMessages.map((m: any) => ({
        content: m.content,
        timestamp: m.created_at
      })),
      recent_matches: recentMatches,
    };

    // Send email notification (lightweight summary, not full JSON)
    try {
      const summaryBody = `
        <h1>Your FWBer Data Export</h1>
        <p>Hello, you requested an export of your personal data.</p>
        <p>Your data export includes:</p>
        <ul>
          <li>Account information</li>
          <li>Profile data</li>
          <li>${exportData.photos.length} photos</li>
          <li>${exportData.recent_messages.length} recent messages</li>
          <li>${exportData.recent_matches.length} matches</li>
        </ul>
        <p>You can download your full data from the Settings page.</p>
        <p>If you did not request this, please secure your account immediately.</p>
      `;
      await sendNotificationEmail(
        user.email || '',
        'FWBer Data Export Request',
        summaryBody
      );
    } catch (emailErr) {
      console.error('[export] Failed to send email notification:', emailErr);
    }

    // Return the export data directly so the frontend can download it
    res.json({
      message: 'Data export generated successfully.',
      status: 'completed',
      data: exportData,
    });
  } catch (err) {
    console.error('[export] error:', err);
    res.status(500).json({ message: 'Failed to process data export' });
  }
});

// GET /api/user/export/status - Export status
router.get('/export/status', authenticate, async (_req: any, res) => {
  res.json({ status: 'none', message: 'No export in progress' });
});

export default router;
