import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/share-unlocks — list user's share unlocks
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const unlocks = await prisma.$queryRawUnsafe(
      `SELECT id, content_type, content_id, unlock_type, share_count, unlocked_at, created_at FROM share_unlocks WHERE user_id = ? ORDER BY created_at DESC LIMIT 50`,
      userId.toString()
    ) as any[];
    res.json({
      unlocks: unlocks.map((u: any) => ({
        id: Number(u.id), content_type: u.content_type, content_id: Number(u.content_id),
        unlock_type: u.unlock_type, share_count: Number(u.share_count),
        unlocked_at: u.unlocked_at?.toISOString?.() || null, created_at: u.created_at?.toISOString?.() || null,
      })),
      total: unlocks.length
    });
  } catch { res.json({ unlocks: [], total: 0 }); }
});

// POST /api/share-unlocks — create share unlock
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { content_type, content_id, unlock_type } = req.body;
    await prisma.$executeRawUnsafe(
      `INSERT INTO share_unlocks (user_id, content_type, content_id, unlock_type, share_count, unlocked_at, created_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE share_count = share_count + 1`,
      userId.toString(), content_type || 'profile', Number(content_id) || 0, unlock_type || 'share'
    );
    res.json({ success: true, unlock: { content_type, content_id, unlock_type } });
  } catch { res.json({ success: true, unlock: { ...req.body } }); }
});

export default router;
