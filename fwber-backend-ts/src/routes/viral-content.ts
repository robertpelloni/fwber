import { Router } from 'express';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/viral-content/:id — Retrieve shared content
router.get('/:id', async (req, res) => {
  try {
    const record = await prisma.viral_contents.findUnique({ where: { id: req.params.id } });
    if (!record) {
      return res.status(404).json({ error: 'Content not found' });
    }

    // Increment view count
    await prisma.viral_contents.update({
      where: { id: req.params.id },
      data: { views: { increment: 1 } },
    });

    const userId = (req as any).user?.id;
    const content = typeof record.content === 'string' ? JSON.parse(record.content) : record.content;

    res.json({
      type: record.type,
      content,
      created_at: record.created_at?.toISOString(),
      views: record.views + 1,
      is_owner: userId ? String(record.user_id) === String(userId) : false,
      reward_claimed: record.reward_claimed,
      user_name: content.user_name || null,
    });
  } catch (err: any) {
    console.error('[viral-content]', err.message);
    res.status(500).json({ error: 'Failed to load content' });
  }
});

export default router;
