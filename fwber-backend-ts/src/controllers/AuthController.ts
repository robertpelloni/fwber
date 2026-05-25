import type { Request, Response } from 'express';
import { z } from 'zod';
import { AuthService } from '../services/AuthService.js';

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
  private authService: AuthService;

  constructor() {
    this.authService = new AuthService();
  }

  register = async (req: Request, res: Response) => {
    try {
      const validated = registerSchema.parse(req.body);
      const result = await this.authService.registerUser(validated);

      res.status(201).json({
        access_token: result.token,
        token_type: 'Bearer',
        user: result.user,
      });
    } catch (error: any) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ errors: error.issues });
      }
      if (error.message === 'User already exists') {
        return res.status(400).json({ message: error.message });
      }
      console.error('[Auth] Registration error:', error.message);
      res.status(500).json({ message: error.message });
    }
  };

  login = async (req: Request, res: Response) => {
    try {
      const validated = loginSchema.parse(req.body);
      const result = await this.authService.loginUser(validated);

      res.json({
        access_token: result.token,
        token_type: 'Bearer',
        user: result.user,
      });
    } catch (error: any) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ errors: error.issues });
      }
      if (error.message === 'Invalid credentials') {
        return res.status(401).json({ message: error.message });
      }
      console.error('[Auth] Login error:', error.message);
      res.status(500).json({ message: error.message });
    }
  };

  me = async (req: any, res: Response) => {
    res.json(this.authService.serialize(req.user));
  };

  forgotPassword = async (req: Request, res: Response) => {
    try {
      const { email } = req.body;
      if (!email) {
        return res.status(400).json({ message: 'Email is required' });
      }

      await this.authService.requestPasswordReset(email);

      res.json({ message: 'If an account with that email exists, a reset link has been sent.' });
    } catch (error: any) {
      console.error('[Auth] Forgot password error:', error.message);
      res.status(500).json({ message: 'Internal server error' });
    }
  };

  resetPassword = async (req: Request, res: Response) => {
    try {
      const { token, password, email, password_confirmation } = req.body;
      if (!token || !password) {
        return res.status(400).json({ message: 'Token and new password are required' });
      }
      if (password_confirmation !== undefined && password !== password_confirmation) {
        return res.status(400).json({ message: 'Passwords do not match' });
      }
      if (password.length < 8) {
        return res.status(400).json({ message: 'Password must be at least 8 characters' });
      }

      await this.authService.executePasswordReset({ token, password });

      res.json({ success: true, message: 'Password has been reset successfully' });
    } catch (error: any) {
      if (error.message === 'Invalid or expired reset token') {
        return res.status(400).json({ message: error.message });
      }
      console.error('[Auth] Reset password error:', error.message);
      res.status(500).json({ message: 'Internal server error' });
    }
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
