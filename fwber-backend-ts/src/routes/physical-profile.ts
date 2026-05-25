import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { generateAvatarImage } from '../lib/wingman-ai.js';

const router = Router();

// Physical profile fields that live in user_profiles
const PHYSICAL_FIELDS = [
  'height_cm', 'body_type', 'hair_color', 'eye_color', 'skin_tone',
  'ethnicity', 'facial_hair', 'tattoos', 'piercings', 'dominant_hand',
  'fitness_level', 'clothing_style', 'avatar_prompt', 'avatar_status',
] as const;

const STRING_BOOLEAN_FIELDS = new Set(['tattoos', 'piercings']);
const NUMERIC_FIELDS = new Set(['height_cm']);

function coerceBooleanFlag(value: unknown): boolean {
  return value === true || value === 'true' || value === 1 || value === '1';
}

function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (Array.isArray(obj)) return obj.map(serialize);
  if (typeof obj === 'object') {
    // Detect Prisma Decimal by {s, e, d} signature
    if (obj.s !== undefined && obj.e !== undefined && Array.isArray(obj.d)) {
      try {
        const parts: string[] = [];
        for (let i = 0; i < obj.d.length; i++) {
          const d = String(obj.d[i]);
          parts.push(i === 0 ? d : d.padStart(7, '0'));
        }
        const digitStr = parts.join('');
        const intDigits = obj.e + 1;
        const sign = obj.s === -1 ? '-' : '';
        if (digitStr.length <= intDigits) {
          return parseFloat(sign + digitStr + '0'.repeat(intDigits - digitStr.length));
        }
        return parseFloat(sign + digitStr.slice(0, intDigits) + '.' + digitStr.slice(intDigits));
      } catch { return 0; }
    }
    if (obj instanceof Date) return obj.toISOString();
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

/** GET /api/physical-profile — read physical fields from user_profiles */
router.get('/', authenticate, async (req: any, res) => {
  try {
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: BigInt(req.user.id) },
    });

    if (!profile) return res.json({ data: {} });

    const data: Record<string, any> = {};
    for (const field of PHYSICAL_FIELDS) {
      const rawValue = (profile as any)[field];

      if (STRING_BOOLEAN_FIELDS.has(field)) {
        data[field] = rawValue == null ? false : coerceBooleanFlag(rawValue);
        continue;
      }

      if (NUMERIC_FIELDS.has(field) && rawValue != null && rawValue !== '') {
        data[field] = Number(rawValue);
        continue;
      }

      data[field] = rawValue ?? null;
    }

    res.json({ data });
  } catch (error: any) {
    console.error('[GET /physical-profile]', error.message);
    res.json({ data: {} });
  }
});

/** PUT /api/physical-profile — update physical fields in user_profiles */
router.put('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const data: Record<string, any> = {};
    for (const field of PHYSICAL_FIELDS) {
      if (req.body[field] === undefined) continue;

      const incomingValue = req.body[field];

      if (STRING_BOOLEAN_FIELDS.has(field)) {
        data[field] = coerceBooleanFlag(incomingValue) ? 'true' : 'false';
        continue;
      }

      if (NUMERIC_FIELDS.has(field)) {
        data[field] = incomingValue === null || incomingValue === '' ? null : Number(incomingValue);
        continue;
      }

      data[field] = incomingValue;
    }

    if (Object.keys(data).length === 0) {
      return res.status(400).json({ message: 'No valid fields to update' });
    }

    const existing = await prisma.user_profiles.findFirst({ where: { user_id: userId } });

    let result;
    if (existing) {
      result = await prisma.user_profiles.update({
        where: { id: existing.id },
        data,
      });
    } else {
      result = await prisma.user_profiles.create({
        data: { user_id: userId, ...data },
      });
    }

    const out: Record<string, any> = {};
    for (const field of PHYSICAL_FIELDS) {
      const rawValue = (result as any)[field];

      if (STRING_BOOLEAN_FIELDS.has(field)) {
        out[field] = rawValue == null ? false : coerceBooleanFlag(rawValue);
        continue;
      }

      if (NUMERIC_FIELDS.has(field) && rawValue != null && rawValue !== '') {
        out[field] = Number(rawValue);
        continue;
      }

      out[field] = rawValue ?? null;
    }

    res.json({ data: out });
  } catch (error: any) {
    console.error('[PUT /physical-profile]', error.message);
    res.status(500).json({ message: error.message || 'Failed to save physical profile' });
  }
});

/** POST /api/physical-profile/avatar/request — request AI avatar generation */
router.post('/avatar/request', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { style } = req.body;

    // Mark avatar as requested so the UI can show status
    const existing = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    if (existing) {
      await prisma.user_profiles.update({
        where: { id: existing.id },
        data: { avatar_status: 'requested' },
      });
    }

    // Trigger async generation (don't await it to avoid blocking response)
    generateAvatarImage(userId, style || 'realistic').catch(err => {
      console.error('[AvatarJob] Background generation failed:', err.message);
    });

    res.json({ success: true, status: 'requested', style: style || 'realistic' });
  } catch (error: any) {
    console.error('[POST /physical-profile/avatar/request]', error.message);
    res.status(500).json({ message: 'Failed to request avatar generation' });
  }
});

export default router;
