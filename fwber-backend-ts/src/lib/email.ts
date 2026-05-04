import nodemailer from 'nodemailer';
import * as dotenv from 'dotenv';
dotenv.config();

// Resend support (modern API-based email)
let resend: any = null;
try {
  const { Resend } = require('resend');
  if (process.env.RESEND_API_KEY) {
    resend = new Resend(process.env.RESEND_API_KEY);
    console.log('[Email] Resend API initialized');
  }
} catch {
  // Resend package not available, fall back to SMTP
}

// SMTP transporter (legacy fallback)
let transporter: nodemailer.Transporter | null = null;
if (process.env.MAIL_HOST && process.env.MAIL_USER && process.env.MAIL_PASS) {
  transporter = nodemailer.createTransport({
    host: process.env.MAIL_HOST,
    port: parseInt(process.env.MAIL_PORT || '587'),
    secure: parseInt(process.env.MAIL_PORT || '587') === 465,
    auth: {
      user: process.env.MAIL_USER,
      pass: process.env.MAIL_PASS,
    },
  });
  console.log('[Email] SMTP transporter configured for', process.env.MAIL_HOST);
} else {
  console.log('[Email] No SMTP credentials found, relying on Resend or console fallback');
}

const MAIL_FROM = process.env.MAIL_FROM || 'noreply@fwber.me';
const FRONTEND_URL = process.env.FRONTEND_URL || 'https://www.fwber.me';

/**
 * Send email using the best available method:
 * 1. Resend API (if RESEND_API_KEY is set)
 * 2. SMTP (if MAIL_HOST/USER/PASS are set and working)
 * 3. Console fallback (log the link so developers can still verify)
 */
async function sendEmail(to: string, subject: string, html: string): Promise<boolean> {
  // Method 1: Resend API
  if (resend) {
    try {
      const { data, error } = await resend.emails.send({
        from: MAIL_FROM,
        to,
        subject,
        html,
      });
      if (error) {
        console.error('[Email] Resend error:', error);
      } else {
        console.log('[Email] Sent via Resend to %s: %s', to, data?.id || 'ok');
        return true;
      }
    } catch (err) {
      console.error('[Email] Resend exception:', err);
    }
  }

  // Method 2: SMTP
  if (transporter) {
    try {
      const info = await transporter.sendMail({
        from: `"fwber" <${MAIL_FROM}>`,
        to,
        subject,
        html,
      });
      console.log('[Email] Sent via SMTP to %s: %s', to, info.messageId);
      return true;
    } catch (err) {
      console.error('[Email] SMTP error:', (err as Error).message);
    }
  }

  // Method 3: Console fallback - extract verification URL and log it
  const urlMatch = html.match(/href="([^"]+)"/);
  if (urlMatch) {
    console.log('[Email-FALLBACK] Verification URL for %s: %s', to, urlMatch[1]);
  }
  console.warn('[Email] All email methods failed for %s - email not sent', to);
  return false;
}

/**
 * Send a verification email to the user
 */
export async function sendVerificationEmail(email: string, token: string): Promise<boolean> {
  const verificationUrl = `${FRONTEND_URL}/verify?token=${token}`;

  const html = `
    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
      <h2>Welcome to fwber!</h2>
      <p>Please click the button below to verify your email address and complete your registration:</p>
      <a href="${verificationUrl}" style="display: inline-block; padding: 12px 24px; background-color: #9333ea; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Verify Email</a>
      <p>If you did not create an account, no further action is required.</p>
      <hr />
      <p style="font-size: 12px; color: #666;">This link will expire in 24 hours.</p>
    </div>
  `;

  return sendEmail(email, 'Verify your email address', html);
}

/**
 * Send a password reset email
 */
export async function sendPasswordResetEmail(email: string, token: string): Promise<boolean> {
  const resetUrl = `${FRONTEND_URL}/reset-password?token=${token}`;

  const html = `
    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
      <h2>Reset Your Password</h2>
      <p>Click the button below to set a new password:</p>
      <a href="${resetUrl}" style="display: inline-block; padding: 12px 24px; background-color: #9333ea; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Reset Password</a>
      <p>If you did not request a password reset, no further action is required.</p>
      <hr />
      <p style="font-size: 12px; color: #666;">This link will expire in 1 hour.</p>
    </div>
  `;

  return sendEmail(email, 'Reset your password', html);
}

/**
 * Send a generic notification email
 */
export async function sendNotificationEmail(email: string, subject: string, content: string): Promise<boolean> {
  const html = `
    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
      ${content}
    </div>
  `;

  return sendEmail(email, subject, html);
}
