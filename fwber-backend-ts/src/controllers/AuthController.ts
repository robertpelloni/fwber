import type { Request, Response } from 'express';
import { z } from 'zod';
import { AuthService } from '../services/AuthService.js';
import { serialize } from '../lib/prisma.js';

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
  private authService = new AuthService();

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
      res.status(400).json({ message: error.message });
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
      res.status(401).json({ message: error.message });
    }
  };

  me = async (req: any, res: Response) => {
    res.json(serialize(req.user));
  };

  forgotPassword = async (req: Request, res: Response) => {
    try {
      const { email } = req.body;
      if (!email) return res.status(400).json({ message: 'Email is required' });

      await this.authService.requestPasswordReset(email);
      res.json({ message: 'If an account with that email exists, a reset link has been sent.' });
    } catch (error: any) {
      res.status(500).json({ message: error.message });
    }
  };

  resetPassword = async (req: Request, res: Response) => {
    try {
      const { token, password } = req.body;
      if (!token || !password) return res.status(400).json({ message: 'Token and password required' });

      await this.authService.executePasswordReset({ token, password });
      res.json({ success: true, message: 'Password has been reset successfully' });
    } catch (error: any) {
      res.status(400).json({ message: error.message });
    }
  };
}
