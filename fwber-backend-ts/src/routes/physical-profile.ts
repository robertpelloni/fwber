import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Physical profile fields that live in user_profiles
const PHYSICAL_FIELDS = [
  'height_cm', 'body_type', 'hair_color', 'eye_color', 'skin_tone',
  'ethnicity', 'facial_hair', 'tattoos', 'piercings', 'dominant_hand',
  'fitness_level', 'clothing_style', 'avatar_prompt', 'avatar_status',
] as const;

function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (obj instanceof Date) return obj;
  if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
  if (typeof obj === 'object') {
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
      data[field] = (profile as any)[field] ?? null;
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
      if (req.body[field] !== undefined) {
        data[field] = req.body[field];
      }
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
      out[field] = (result as any)[field] ?? null;
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

    // TODO: Queue actual avatar generation job here when provider is wired up

    res.json({ success: true, status: 'requested', style: style || 'realistic' });
  } catch (error: any) {
    console.error('[POST /physical-profile/avatar/request]', error.message);
    res.status(500).json({ message: 'Failed to request avatar generation' });
  }
});

export default router;
