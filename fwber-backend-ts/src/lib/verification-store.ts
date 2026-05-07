import redis from './redis.js';
import crypto from 'crypto';

// Replaced in-memory store with Redis for production scalability
export const VerificationStore = {
  async saveToken(email: string, token: string): Promise<void> {
    // Store token with 24-hour expiration
    await redis.set(`verification:${token}`, email, 'EX', 86400);
  },

  async getEmailForToken(token: string): Promise<string | null> {
    return await redis.get(`verification:${token}`);
  },

  async removeToken(token: string): Promise<void> {
    await redis.del(`verification:${token}`);
  }
};

export async function createVerificationToken(userId: bigint | string | number, email: string): Promise<string> {
    const token = crypto.randomBytes(32).toString('hex');
    await redis.set(`verification:${token}`, JSON.stringify({ userId: userId.toString(), email }), 'EX', 86400);
    return token;
}

export async function consumeVerificationToken(token: string): Promise<{userId: bigint, email: string} | null> {
    const data = await redis.get(`verification:${token}`);
    if (!data) return null;
    await redis.del(`verification:${token}`);
    const parsed = JSON.parse(data);
    return { userId: BigInt(parsed.userId), email: parsed.email };
}
