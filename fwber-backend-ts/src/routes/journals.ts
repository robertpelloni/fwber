import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';

const router = Router();
router.use(authenticate);

function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (obj instanceof Date) return obj.toISOString();
  if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

// GET /api/journals — list user's journal entries
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const entries: any[] = await prisma.$queryRawUnsafe(
      'SELECT * FROM journal_entries WHERE user_id = ? ORDER BY created_at DESC LIMIT 30',
      userId.toString()
    );

    res.json(serialize(entries));
  } catch (error: any) {
    console.error('[Journals] List error:', error.message);
    res.json([]);
  }
});

// POST /api/journals — create a journal entry
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { title, content, mood, is_private } = req.body;

    if (!content) {
      return res.status(400).json({ message: 'Content is required' });
    }

    await prisma.$executeRawUnsafe(
      'INSERT INTO journal_entries (user_id, title, content, mood, is_private, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
      userId.toString(), title || null, content, mood || null, is_private !== false ? 1 : 0
    );

    // Check achievements after journal creation (dear diary)
    checkAndUnlockAchievements(userId).catch(() => {});

    res.json({ success: true });
  } catch (error: any) {
    console.error('[Journals] Create error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to create entry' });
  }
});

// PUT /api/journals/:id — update a journal entry
router.put('/:id', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const entryId = BigInt(req.params.id);
    const { title, content, mood, is_private } = req.body;

    const entries: any[] = await prisma.$queryRawUnsafe(
      'SELECT * FROM journal_entries WHERE id = ? AND user_id = ?',
      entryId.toString(), userId.toString()
    );
    if (!entries.length) {
      return res.status(404).json({ message: 'Entry not found' });
    }

    const sets: string[] = [];
    const params: any[] = [];
    if (title !== undefined) { sets.push('title = ?'); params.push(title); }
    if (content !== undefined) { sets.push('content = ?'); params.push(content); }
    if (mood !== undefined) { sets.push('mood = ?'); params.push(mood); }
    if (is_private !== undefined) { sets.push('is_private = ?'); params.push(is_private ? 1 : 0); }
    sets.push('updated_at = NOW()');
    params.push(entryId.toString());
    params.push(userId.toString());

    await prisma.$executeRawUnsafe(
      'UPDATE journal_entries SET ' + sets.join(', ') + ' WHERE id = ? AND user_id = ?',
      ...params
    );

    res.json({ success: true });
  } catch (error: any) {
    console.error('[Journals] Update error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to update entry' });
  }
});

// DELETE /api/journals/:id — delete a journal entry
router.delete('/:id', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const entryId = BigInt(req.params.id);

    const entries: any[] = await prisma.$queryRawUnsafe(
      'SELECT id FROM journal_entries WHERE id = ? AND user_id = ?',
      entryId.toString(), userId.toString()
    );
    if (!entries.length) {
      return res.status(404).json({ message: 'Entry not found' });
    }

    await prisma.$executeRawUnsafe(
      'DELETE FROM journal_entries WHERE id = ? AND user_id = ?',
      entryId.toString(), userId.toString()
    );
    res.json({ success: true });
  } catch (error: any) {
    console.error('[Journals] Delete error:', error.message);
    res.status(500).json({ success: false });
  }
});

export default router;
