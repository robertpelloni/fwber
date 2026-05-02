import nodemailer from 'nodemailer';
import * as dotenv from 'dotenv';

dotenv.config();

// Create a transporter using SMTP or other transport mechanisms
// For development, you can use ethereal.email or a local mail server
const transporter = nodemailer.createTransport({
  host: process.env.MAIL_HOST || 'smtp.mailtrap.io',
  port: parseInt(process.env.MAIL_PORT || '2525'),
  auth: {
    user: process.env.MAIL_USER,
    pass: process.env.MAIL_PASS,
  },
});

/**
 * Send a verification email to the user
 */
export async function sendVerificationEmail(email: string, token: string) {
  const verificationUrl = `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/verify?token=${token}`;

  const mailOptions = {
    from: `"fwber" <${process.env.MAIL_FROM || 'noreply@fwber.me'}>`,
    to: email,
    subject: 'Verify your email address',
    html: `
      <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
        <h2>Welcome to fwber!</h2>
        <p>Please click the button below to verify your email address and complete your registration:</p>
        <a href="${verificationUrl}" style="display: inline-block; padding: 12px 24px; background-color: #9333ea; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Verify Email</a>
        <p>If you did not create an account, no further action is required.</p>
        <hr />
        <p style="font-size: 12px; color: #666;">This link will expire in 24 hours.</p>
      </div>
    `,
  };

  try {
    const info = await transporter.sendMail(mailOptions);
    console.log('[Email] Verification sent to %s: %s', email, info.messageId);
    return true;
  } catch (error) {
    console.error('[Email] Error sending verification email:', error);
    return false;
  }
}

/**
 * Send a generic notification email
 */
export async function sendNotificationEmail(email: string, subject: string, content: string) {
  const mailOptions = {
    from: `"fwber" <${process.env.MAIL_FROM || 'noreply@fwber.me'}>`,
    to: email,
    subject: subject,
    html: `
      <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
        ${content}
      </div>
    `,
  };

  try {
    await transporter.sendMail(mailOptions);
    return true;
  } catch (error) {
    console.error('[Email] Error sending notification email:', error);
    return false;
  }
}
