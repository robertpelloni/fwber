/**
 * Redis-backed verification token store.
 * Replaces the in-memory store for horizontal scalability.
 */
import crypto from 'crypto';
import redis from './redis.js';

interface TokenData {
  userId: bigint;
  email: string;
  expires: number;
}

const REDIS_PREFIX = 'verification_token:';

/**
 * Generate a new verification token for a user.
 * Returns the token string.
 */
export async function createVerificationToken(userId: bigint, email: string, ttlMs = 24 * 60 * 60 * 1000): Promise<string> {
  const token = crypto.randomBytes(32).toString('hex');
  const data: TokenData = { userId, email, expires: Date.now() + ttlMs };
  await redis.setex(`${REDIS_PREFIX}${token}`, Math.floor(ttlMs / 1000), JSON.stringify(data));
  return token;
}

/**
 * Validate and consume a verification token.
 * Returns the token data if valid, or null if invalid/expired.
 */
export async function consumeVerificationToken(token: string): Promise<TokenData | null> {
  const key = `${REDIS_PREFIX}${token}`;
  const dataStr = await redis.get(key);
  if (!dataStr) return null;

  const data: TokenData = JSON.parse(dataStr);
  if (data.expires < Date.now()) {
    await redis.del(key);
    return null;
  }

  await redis.del(key); // One-time use
  return data;
}

/**
 * Check if a token exists without consuming it.
 */
export async function hasVerificationToken(token: string): Promise<boolean> {
  const key = `${REDIS_PREFIX}${token}`;
  const dataStr = await redis.get(key);
  if (!dataStr) return false;

  const data: TokenData = JSON.parse(dataStr);
  if (data.expires < Date.now()) {
    await redis.del(key);
    return false;
  }
  return true;
}
