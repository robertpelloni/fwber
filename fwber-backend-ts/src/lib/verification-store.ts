/**
 * Shared in-memory verification token store.
 * Used by both the auth controller (registration) and the email route
 * so that tokens generated at registration can be verified later.
 *
 * For production, replace with a database-backed store.
 */
import crypto from 'crypto';

interface TokenData {
  userId: bigint;
  email: string;
  expires: number;
}

const tokens = new Map<string, TokenData>();

// Clean expired tokens every 10 minutes
setInterval(() => {
  const now = Date.now();
  for (const [token, data] of tokens) {
    if (data.expires < now) tokens.delete(token);
  }
}, 10 * 60 * 1000);

/**
 * Generate a new verification token for a user.
 * Returns the token string.
 */
export function createVerificationToken(userId: bigint, email: string, ttlMs = 24 * 60 * 60 * 1000): string {
  const token = crypto.randomBytes(32).toString('hex');
  tokens.set(token, { userId, email, expires: Date.now() + ttlMs });
  return token;
}

/**
 * Validate and consume a verification token.
 * Returns the token data if valid, or null if invalid/expired.
 */
export function consumeVerificationToken(token: string): TokenData | null {
  const data = tokens.get(token);
  if (!data) return null;
  if (data.expires < Date.now()) {
    tokens.delete(token);
    return null;
  }
  tokens.delete(token); // One-time use
  return data;
}

/**
 * Check if a token exists without consuming it.
 */
export function hasVerificationToken(token: string): boolean {
  const data = tokens.get(token);
  if (!data) return false;
  if (data.expires < Date.now()) {
    tokens.delete(token);
    return false;
  }
  return true;
}
