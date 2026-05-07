import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/scrapbook
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const entries = await prisma.scrapbook_entries.findMany({
      where: { user_id: userId },
      orderBy: [{ is_pinned: 'desc' as const }, { created_at: 'desc' as const }],
      take: 50,
      include: { users_scrapbook_entries_match_user_idTousers: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } } }
    });
    const data = entries.map((e: any) => {
      const mu = e.users_scrapbook_entries_match_user_idTousers;
      const mp = Array.isArray(mu?.user_profiles) ? mu.user_profiles[0] : mu?.user_profiles;
      return {
        id: Number(e.id), match_user_id: Number(e.match_user_id), type: e.type,
        content: e.content, media_url: e.media_url, media_type: e.media_type,
        emoji: e.emoji, color: e.color, is_pinned: Boolean(e.is_pinned), created_at: e.created_at,
        match_user: { id: Number(mu?.id), name: mp?.display_name || mu?.name || 'Unknown', avatar_url: mp?.avatar_url || null }
      };
    });
    res.json({ entries: data, total: data.length });
  } catch (err: any) {
    console.error('[scrapbook] list error:', err.message);
    res.json({ entries: [], total: 0 });
  }
});

// POST /api/scrapbook
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { match_user_id, type, content, media_url, media_type, emoji, color } = req.body;
    if (!match_user_id || !type || !content) return res.status(422).json({ error: 'match_user_id, type, and content required' });
    const entry = await prisma.scrapbook_entries.create({
      data: { user_id: userId, match_user_id: BigInt(match_user_id), type, content, media_url: media_url || null, media_type: media_type || null, emoji: emoji || null, color: color || null }
    });
    res.status(201).json({ success: true, entry: { id: Number(entry.id), type: entry.type, content: entry.content, created_at: entry.created_at } });
  } catch (err: any) {
    console.error('[scrapbook] create error:', err.message);
    res.status(500).json({ error: 'Failed to create entry' });
  }
});

// DELETE /api/scrapbook/:id
router.delete('/:id', async (req: any, res) => {
  try {
    const entry = await prisma.scrapbook_entries.findUnique({ where: { id: BigInt(req.params.id) } });
    if (!entry || entry.user_id !== BigInt(req.user.id)) return res.status(404).json({ error: 'Entry not found' });
    await prisma.scrapbook_entries.delete({ where: { id: entry.id } });
    res.json({ success: true });
  } catch (err: any) {
    res.status(500).json({ error: 'Failed to delete entry' });
  }
});

export default router;
