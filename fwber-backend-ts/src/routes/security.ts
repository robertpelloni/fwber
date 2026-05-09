import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// POST /api/security/keys — store or update public key for E2E encryption
router.post('/keys', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { public_key, private_key, key_type, device_id } = req.body;
    if (!public_key) return res.status(400).json({ error: 'public_key is required' });

    // Upsert: one key per user per device
    const existing = await prisma.user_public_keys.findFirst({
      where: { user_id: userId, device_id: device_id || null }
    });

    if (existing) {
      await prisma.user_public_keys.update({
        where: { id: existing.id },
        data: {
          public_key,
          private_key: private_key || existing.private_key,
          key_type: key_type || 'ECDH',
          last_rotated_at: new Date(),
        }
      });
    } else {
      await prisma.user_public_keys.create({
        data: {
          user_id: userId,
          public_key,
          private_key: private_key || null,
          key_type: key_type || 'ECDH',
          device_id: device_id || null,
          last_rotated_at: new Date(),
        }
      });
    }

    res.json({ message: 'Public key stored', stored: true });
  } catch (err: any) {
    console.error('[security] POST keys error:', err.message);
    res.status(500).json({ error: 'Failed to store key' });
  }
});

// GET /api/security/keys — retrieve current user's public key
router.get('/keys', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const key = await prisma.user_public_keys.findFirst({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' as const }
    });
    res.json({
      public_key: key?.public_key || null,
      key_type: key?.key_type || null,
      device_id: key?.device_id || null,
      last_rotated_at: key?.last_rotated_at?.toISOString() || null,
    });
  } catch (err: any) {
    console.error('[security] GET keys error:', err.message);
    res.json({ public_key: null });
  }
});

// GET /api/security/keys/me — get current user's key (alias for frontend compat)
router.get('/keys/me', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const key = await prisma.user_public_keys.findFirst({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' as const }
    });
    res.json({
      public_key: key?.public_key || null,
      key_type: key?.key_type || null,
      device_id: key?.device_id || null,
    });
  } catch (err: any) {
    res.json({ public_key: null });
  }
});

// GET /api/security/keys/:userId — get another user's public key
router.get('/keys/:userId', async (req: any, res) => {
  try {
    const targetUserId = BigInt(req.params.userId);
    const key = await prisma.user_public_keys.findFirst({
      where: { user_id: targetUserId },
      orderBy: { created_at: 'desc' as const }
    });
    res.json({
      user_id: Number(targetUserId),
      public_key: key?.public_key || null,
      key_type: key?.key_type || null,
    });
  } catch (err: any) {
    res.json({ user_id: Number(req.params.userId), public_key: null });
  }
});

export default router;
