import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// Helper: serialize entry
function serializeEntry(e: any) {
  const mu = e.users_scrapbook_entries_match_user_idTousers;
  const mp = Array.isArray(mu?.user_profiles) ? mu.user_profiles[0] : mu?.user_profiles;
  return {
    id: Number(e.id), user_id: Number(e.user_id), match_user_id: Number(e.match_user_id),
    type: e.type, content: e.content, media_url: e.media_url, media_type: e.media_type,
    emoji: e.emoji, color: e.color, is_pinned: Boolean(e.is_pinned),
    is_mine: true, created_at: e.created_at, updated_at: e.updated_at,
    match_user: { id: Number(mu?.id), name: mp?.display_name || mu?.name || 'Unknown', avatar_url: mp?.avatar_url || null }
  };
}

const includeMatchUser = {
  users_scrapbook_entries_match_user_idTousers: {
    select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } }
  }
};

// GET /api/scrapbook - All entries
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const entries = await prisma.scrapbook_entries.findMany({
      where: { user_id: userId },
      orderBy: [{ is_pinned: 'desc' as const }, { created_at: 'desc' as const }],
      take: 50, include: includeMatchUser
    });
    const data = entries.map(serializeEntry);
    const pinned = data.filter((e: any) => e.is_pinned).length;
    res.json({ entries: data, meta: { total: data.length, pinned } });
  } catch (err: any) {
    console.error('[scrapbook] list error:', err.message);
    res.json({ entries: [], meta: { total: 0, pinned: 0 } });
  }
});

// GET /api/scrapbook/:matchId - Entries for specific match
router.get('/:matchId', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchUserId = BigInt(req.params.matchId);
    const entries = await prisma.scrapbook_entries.findMany({
      where: { user_id: userId, match_user_id: matchUserId },
      orderBy: [{ is_pinned: 'desc' as const }, { created_at: 'desc' as const }],
      take: 50, include: includeMatchUser
    });
    const data = entries.map(serializeEntry);
    const pinned = data.filter((e: any) => e.is_pinned).length;
    res.json({ entries: data, meta: { total: data.length, pinned } });
  } catch (err: any) {
    console.error('[scrapbook] match entries error:', err.message);
    res.json({ entries: [], meta: { total: 0, pinned: 0 } });
  }
});

// POST /api/scrapbook - Create entry (accept both match_id and match_user_id)
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { match_id, match_user_id, type, content, media_url, media_type, emoji, color } = req.body;
    const matchUserId = BigInt(match_user_id || match_id);
    if (!matchUserId || !type || !content) return res.status(422).json({ error: 'match_id, type, and content required' });
    const entry = await prisma.scrapbook_entries.create({
      data: { user_id: userId, match_user_id: matchUserId, type, content, media_url: media_url || null, media_type: media_type || null, emoji: emoji || null, color: color || null }
    });
    res.status(201).json({ entry: { id: Number(entry.id), type: entry.type, content: entry.content, created_at: entry.created_at, is_pinned: false } });
  } catch (err: any) {
    console.error('[scrapbook] create error:', err.message);
    res.status(500).json({ error: 'Failed to create entry' });
  }
});

// PATCH /api/scrapbook/:id/pin - Toggle pin
router.patch('/:id/pin', async (req: any, res) => {
  try {
    const entryId = BigInt(req.params.id);
    const entry = await prisma.scrapbook_entries.findUnique({ where: { id: entryId } });
    if (!entry || entry.user_id !== BigInt(req.user.id)) return res.status(404).json({ error: 'Entry not found' });
    const updated = await prisma.scrapbook_entries.update({ where: { id: entryId }, data: { is_pinned: !entry.is_pinned } });
    res.json({ entry: { id: Number(updated.id), is_pinned: Boolean(updated.is_pinned) } });
  } catch (err: any) {
    res.status(500).json({ error: 'Failed to toggle pin' });
  }
});

// DELETE /api/scrapbook/:id
router.delete('/:id', async (req: any, res) => {
  try {
    const entryId = BigInt(req.params.id);
    const entry = await prisma.scrapbook_entries.findUnique({ where: { id: entryId } });
    if (!entry || entry.user_id !== BigInt(req.user.id)) return res.status(404).json({ error: 'Entry not found' });
    await prisma.scrapbook_entries.delete({ where: { id: entryId } });
    res.json({ success: true });
  } catch (err: any) {
    res.status(500).json({ error: 'Failed to delete entry' });
  }
});

export default router;
