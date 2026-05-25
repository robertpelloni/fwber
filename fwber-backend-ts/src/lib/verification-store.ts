/**
 * Database-backed verification token store.
 * Used by both the auth controller (registration) and the email route
 * so that tokens generated at registration can be verified later.
 * Survives PM2 restarts unlike the previous in-memory implementation.
 */
import crypto from 'crypto';
import prisma from './prisma.js';

interface TokenData {
  userId: bigint;
  email: string;
  expires: number;
}

// Clean expired tokens every 10 minutes
setInterval(async () => {
  try {
    await prisma.verification_tokens.deleteMany({
      where: { expires_at: { lt: new Date() } },
    });
  } catch (_err: any) {
    // Silently ignore cleanup errors
  }
}, 10 * 60 * 1000);

/**
 * Generate a new verification token for a user.
 * Returns the token string.
 */
export async function createVerificationToken(userId: bigint, email: string, ttlMs = 24 * 60 * 60 * 1000): Promise<string> {
  const token = crypto.randomBytes(32).toString('hex');
  const expiresAt = new Date(Date.now() + ttlMs);

  try {
    await prisma.verification_tokens.create({
      data: {
        token,
        user_id: userId,
        email,
        expires_at: expiresAt,
      },
    });
  } catch (err: any) {
    console.error('[VerificationStore] Failed to create token:', err.message);
    // Fallback: try again with a different token
    const token2 = crypto.randomBytes(32).toString('hex');
    await prisma.verification_tokens.create({
      data: {
        token: token2,
        user_id: userId,
        email,
        expires_at: expiresAt,
      },
    });
    return token2;
  }

  return token;
}

/**
 * Validate and consume a verification token.
 * Returns the token data if valid, or null if invalid/expired.
 */
export async function consumeVerificationToken(token: string): Promise<TokenData | null> {
  try {
    const record = await prisma.verification_tokens.findUnique({
      where: { token },
    });

    if (!record) return null;

    // Check expiry
    if (new Date(record.expires_at) < new Date()) {
      await prisma.verification_tokens.delete({ where: { token } }).catch(() => {});
      return null;
    }

    const data: TokenData = {
      userId: record.user_id,
      email: record.email,
      expires: new Date(record.expires_at).getTime(),
    };

    // One-time use: delete after reading
    await prisma.verification_tokens.delete({ where: { token } }).catch(() => {});

    return data;
  } catch (err: any) {
    console.error('[VerificationStore] consume error:', err.message);
    return null;
  }
}

/**
 * Check if a token exists without consuming it.
 */
export async function hasVerificationToken(token: string): Promise<boolean> {
  try {
    const record = await prisma.verification_tokens.findUnique({
      where: { token },
    });

    if (!record) return false;

    if (new Date(record.expires_at) < new Date()) {
      await prisma.verification_tokens.delete({ where: { token } }).catch(() => {});
      return false;
    }

    return true;
  } catch (_err: any) {
    return false;
  }
}
