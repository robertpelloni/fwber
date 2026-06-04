import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

/**
 * Recursively convert BigInt values to String for JSON serialization to prevent precision loss.
 * Although IDs currently fit in Number (up to 2^53-1), using Strings ensures long-term safety.
 */
export const serialize = (obj: any): any => {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return obj.toString();
  if (obj && typeof obj === 'object' && typeof obj.toFixed === 'function' && 'd' in obj && 'e' in obj) return Number(obj.toString());
  if (obj instanceof Date) return obj.toISOString();
  if (Array.isArray(obj)) return obj.map(serialize);
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) {
      out[key] = serialize(obj[key]);
    }
    return out;
  }
  return obj;
};

const SENSITIVE_FIELDS = [
  'password', 'decoy_password', 'two_factor_secret',
  'two_factor_recovery_codes', 'remember_token', 'merchant_secret',
  'stripe_customer_id', 'provider_id', 'provider_token',
  'id_verification_data', 'last_login_ip'
];

/**
 * Remove sensitive fields from a user object before sending it to the client.
 */
export const sanitizeUser = (obj: any): any => {
  if (!obj || typeof obj !== 'object') return obj;
  if (obj instanceof Date) return obj.toISOString();
  if (Array.isArray(obj)) return obj.map(sanitizeUser);

  const out: any = {};
  for (const key of Object.keys(obj)) {
    if (SENSITIVE_FIELDS.includes(key)) continue;
    out[key] = sanitizeUser(obj[key]);
  }
  return out;
};

export default prisma;
