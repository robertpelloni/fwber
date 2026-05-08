import type { Request, Response } from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { z } from 'zod';
import prisma from '../lib/prisma.js';

import { sendVerificationEmail, sendPasswordResetEmail } from '../lib/email.js';
import { createVerificationToken } from '../lib/verification-store.js';
import crypto from 'crypto';

const registerSchema = z.object({
  name: z.string().min(2),
  email: z.string().email(),
  password: z.string().min(8),
  referral_code: z.string().optional(),
});

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string(),
});

export class AuthController {
  private generateToken(user: { id: bigint; email: string; role: string }) {
    return jwt.sign(
      { id: Number(user.id), email: user.email, role: user.role },
      process.env.JWT_SECRET || 'secret',
      { expiresIn: '30d' }
    );
  }

  /** Recursively convert all BigInt values to Number in an object */
  private serialize(obj: any): any {
    if (obj === null || obj === undefined) return obj;
    if (typeof obj === 'bigint') return Number(obj);
    if (obj instanceof Date) return obj;
    if (Array.isArray(obj)) return obj.map((v: any) => this.serialize(v));
    if (typeof obj === 'object') {
      const out: any = {};
      for (const key of Object.keys(obj)) {
        out[key] = this.serialize(obj[key]);
      }
      return out;
    }
    return obj;
  }

  private SENSITIVE_FIELDS = [
    'password', 'decoy_password', 'two_factor_secret',
    'two_factor_recovery_codes', 'remember_token', 'merchant_secret',
  ];

  private sanitizeUser(obj: any): any {
    if (!obj || typeof obj !== 'object') return obj;
    if (Array.isArray(obj)) return obj.map((v: any) => this.sanitizeUser(v));
    const out: any = {};
    for (const key of Object.keys(obj)) {
      if (this.SENSITIVE_FIELDS.includes(key)) continue;
      out[key] = this.sanitizeUser(obj[key]);
    }
    return out;
  }

  private async hydrateUser(user: any) {
    const referralsCount = await prisma.users.count({
      where: { referrer_id: user.id }
    });

    return this.sanitizeUser(this.serialize({
      ...user,
      referrals_count: referralsCount,
      vouches_count: 0,
    }));
  }

  register = async (req: Request, res: Response) => {
    try {
      const validated = registerSchema.parse(req.body);

      const existingUser = await prisma.users.findUnique({
        where: { email: validated.email }
      });

      if (existingUser) {
        return res.status(400).json({ message: 'User already exists' });
      }

      const hashedPassword = await bcrypt.hash(validated.password, 10);

      // Handle referral code
      let referrerId: bigint | null = null;
      if (validated.referral_code) {
        const referrer = await prisma.users.findFirst({
          where: { referral_code: validated.referral_code }
        });
        if (referrer) {
          referrerId = referrer.id;
        }
      }

      const user = await prisma.users.create({
        data: {
          name: validated.name,
          email: validated.email,
          password: hashedPassword,
          role: 'user',
          tier: 'free',
          referral_code: Math.random().toString(36).substring(2, 8).toUpperCase(),
          referrer_id: referrerId,
        },
      });

      // Reward referrer with 50 tokens
      if (referrerId) {
        await prisma.users.update({
          where: { id: referrerId },
          data: {
            token_balance: { increment: 50 }
          }
        });
      }

      // Initialize profile
      await prisma.user_profiles.create({
        data: {
          user_id: user.id,
          display_name: user.name,
        },
      });

      // Welcome bonus: 50 FWB tokens
      await prisma.users.update({ where: { id: user.id }, data: { token_balance: 50 } });
      try {
        await prisma.wallet_transactions.create({ data: { user_id: user.id, amount: 50, type: 'reward', description: 'Welcome bonus — 50 free tokens!', created_at: new Date() } });
      } catch (_) {}

      // Seed default notification preferences
      const notifTypes = ['new_match', 'new_message', 'friend_request', 'event_invitation', 'gift_received', 'profile_view', 'achievement_unlocked', 'daily_reminder', 'marketing', 'proximity_alert'];
      try {
        await prisma.notification_preferences.createMany({
          data: notifTypes.map(type => ({ user_id: user.id, type, mail: true, push: true, database: true }))
        });
      } catch (_) {}

      // Send verification email
      try {
        const verificationToken = await createVerificationToken(user.id, user.email);
        await sendVerificationEmail(user.email, verificationToken);
      } catch (err) {
        console.error('[Auth] Failed to send initial verification email:', err);
      }

      const token = this.generateToken(user);
      const hydrated = await this.hydrateUser(user);

      res.status(201).json({
        access_token: token,
        token_type: 'Bearer',
        user: hydrated,
      });
    } catch (error: any) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ errors: error.issues });
      }
      console.error('[Auth] Registration error:', error.message);
      res.status(500).json({ message: error.message });
    }
  };

  login = async (req: Request, res: Response) => {
    try {
      const { email, password } = loginSchema.parse(req.body);

      const user = await prisma.users.findUnique({
        where: { email },
        include: { user_profiles: true }
      });

      if (!user) {
        return res.status(401).json({ message: 'Invalid credentials' });
      }

      const isPasswordValid = await bcrypt.compare(password, user.password);

      // Decoy password logic
      const isDecoyAuth =
        !isPasswordValid &&
        user.decoy_password !== null &&
        await bcrypt.compare(password, user.decoy_password!);

      if (!isPasswordValid && !isDecoyAuth) {
        return res.status(401).json({ message: 'Invalid credentials' });
      }

      let finalUser = user;
      if (isDecoyAuth && user.decoy_user_id) {
        const decoyUser = await prisma.users.findUnique({
          where: { id: user.decoy_user_id },
          include: { user_profiles: true }
        });
        if (decoyUser) {
          finalUser = decoyUser;
        }
      }

      const token = this.generateToken(finalUser);
      const hydrated = await this.hydrateUser(finalUser);

      res.json({
        access_token: token,
        token_type: 'Bearer',
        user: hydrated,
      });
    } catch (error: any) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ errors: error.issues });
      }
      console.error('[Auth] Login error:', error.message);
      res.status(500).json({ message: error.message });
    }
  };

  me = async (req: any, res: Response) => {
    res.json(this.serialize(req.user));
  };

  /**
   * POST /api/auth/forgot-password
   * Generate a password reset token and send it via email
   */
  forgotPassword = async (req: Request, res: Response) => {
    try {
      const { email } = req.body;
      if (!email) {
        return res.status(400).json({ message: 'Email is required' });
      }

      const user = await prisma.users.findUnique({ where: { email } });
      // Always return success to prevent email enumeration
      if (!user) {
        return res.json({ message: 'If an account with that email exists, a reset link has been sent.' });
      }

      // Generate reset token (1 hour expiry)
      const token = crypto.randomBytes(32).toString('hex');
      const expiresAt = new Date(Date.now() + 60 * 60 * 1000);

      // Store in database (works across all cluster workers)
      await prisma.password_reset_tokens.upsert({
        where: { email_user_id: { email: user.email!, user_id: user.id } },
        create: {
          email: user.email!,
          user_id: user.id,
          token,
          expires_at: expiresAt,
        },
        update: {
          token,
          expires_at: expiresAt,
        },
      });

      // Clean up expired tokens periodically
      try {
        await prisma.password_reset_tokens.deleteMany({
          where: { expires_at: { lt: new Date() } },
        });
      } catch (_) {}

      // Send reset email
      try {
        await sendPasswordResetEmail(user.email!, token);
        console.log('[Auth] Password reset email sent to %s', user.email);
      } catch (err) {
        console.error('[Auth] Failed to send password reset email:', err);
      }
      // Always log the reset URL for development/debugging
      console.log('[Auth] Password reset URL: %s/reset-password?token=%s',
        process.env.FRONTEND_URL || 'https://www.fwber.me', token);

      res.json({ message: 'If an account with that email exists, a reset link has been sent.' });
    } catch (error: any) {
      console.error('[Auth] Forgot password error:', error.message);
      res.status(500).json({ message: 'Internal server error' });
    }
  };

  /**
   * POST /api/auth/reset-password
   * Reset password using a valid reset token
   */
  resetPassword = async (req: Request, res: Response) => {
    try {
      const { token, password, email, password_confirmation } = req.body;
      if (!token || !password) {
        return res.status(400).json({ message: 'Token and new password are required' });
      }
      // Validate password confirmation if provided
      if (password_confirmation !== undefined && password !== password_confirmation) {
        return res.status(400).json({ message: 'Passwords do not match' });
      }
      if (password.length < 8) {
        return res.status(400).json({ message: 'Password must be at least 8 characters' });
      }

      // Look up token in database
      const resetRecord = await prisma.password_reset_tokens.findFirst({
        where: { token },
      });
      if (!resetRecord || resetRecord.expires_at < new Date()) {
        // Delete expired token if found
        if (resetRecord) {
          await prisma.password_reset_tokens.delete({
            where: { email_user_id: { email: resetRecord.email, user_id: resetRecord.user_id } },
          }).catch(() => {});
        }
        return res.status(400).json({ message: 'Invalid or expired reset token' });
      }

      // Hash the new password
      const hashedPassword = await bcrypt.hash(password, 10);

      // Update the user's password
      await prisma.users.update({
        where: { id: resetRecord.user_id },
        data: { password: hashedPassword },
      });

      // Remove the used token
      await prisma.password_reset_tokens.delete({
        where: { email_user_id: { email: resetRecord.email, user_id: resetRecord.user_id } },
      }).catch(() => {});

      console.log('[Auth] Password reset successful for user', resetRecord.user_id.toString());
      res.json({ success: true, message: 'Password has been reset successfully' });
    } catch (error: any) {
      console.error('[Auth] Reset password error:', error.message);
      res.status(500).json({ message: 'Internal server error' });
    }
  };
}
