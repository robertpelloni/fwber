import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/topics — list all topics
router.get('/', authenticate, async (req: any, res) => {
  try {
    const topics = await prisma.topics.findMany({
      orderBy: { follower_count: 'desc' as const }
    });
    res.json({
      topics: topics.map((t: any) => ({
        id: Number(t.id), slug: t.slug, label: t.label, emoji: t.emoji,
        category: t.category, description: t.description, follower_count: Number(t.follower_count),
      })),
      total: topics.length
    });
  } catch (err: any) {
    console.error('[topics] GET error:', err.message);
    res.json({ topics: [], total: 0 });
  }
});

// GET /api/topics/featured — popular topics
router.get('/featured', authenticate, async (_req: any, res) => {
  try {
    const topics = await prisma.topics.findMany({
      orderBy: { follower_count: 'desc' as const },
      take: 10
    });
    res.json({ topics: topics.map((t: any) => ({
      id: Number(t.id), slug: t.slug, label: t.label, emoji: t.emoji,
      category: t.category, follower_count: Number(t.follower_count),
    })), total: topics.length });
  } catch { res.json({ topics: [], total: 0 }); }
});

// GET /api/topics/followed — user's followed topics
router.get('/followed', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const follows = await prisma.topic_user_follows.findMany({
      where: { user_id: userId },
      include: { topics: true }
    });
    res.json({ topics: follows.map((f: any) => ({
      id: Number(f.topics.id), slug: f.topics.slug, label: f.topics.label,
      emoji: f.topics.emoji, category: f.topics.category,
    }))});
  } catch (err: any) {
    console.error('[topics] followed error:', err.message);
    res.json({ topics: [] });
  }
});

// GET /api/topics/:slug — single topic
router.get('/:slug', authenticate, async (req: any, res) => {
  try {
    const topic = await prisma.topics.findUnique({ where: { slug: req.params.slug } });
    if (!topic) return res.json({ topic: null });
    res.json({ topic: {
      id: Number(topic.id), slug: topic.slug, label: topic.label, emoji: topic.emoji,
      category: topic.category, description: topic.description, follower_count: Number(topic.follower_count),
    }});
  } catch { res.json({ topic: null }); }
});

// POST /api/topics/:slug/follow
router.post('/:slug/follow', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const topic = await prisma.topics.findUnique({ where: { slug: req.params.slug } });
    if (!topic) return res.status(404).json({ error: 'Topic not found' });

    await prisma.topic_user_follows.upsert({
      where: { topic_id_user_id: { user_id: userId, topic_id: topic.id } },
      update: {},
      create: { user_id: userId, topic_id: topic.id }
    }).catch(async () => {
      await prisma.topic_user_follows.create({ data: { user_id: userId, topic_id: topic.id } });
    });

    await prisma.topics.update({
      where: { id: topic.id },
      data: { follower_count: { increment: 1 } }
    }).catch(() => {});

    res.json({ topic: { slug: topic.slug, followed: true } });
  } catch (err: any) {
    console.error('[topics] follow error:', err.message);
    res.json({ topic: { followed: true } });
  }
});

// DELETE /api/topics/:slug/follow
router.delete('/:slug/follow', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const topic = await prisma.topics.findUnique({ where: { slug: req.params.slug } });
    if (!topic) return res.status(404).json({ error: 'Topic not found' });

    await prisma.topic_user_follows.deleteMany({
      where: { user_id: userId, topic_id: topic.id }
    });

    await prisma.topics.update({
      where: { id: topic.id },
      data: { follower_count: { decrement: 1 } }
    }).catch(() => {});

    res.json({ topic: { slug: topic.slug, followed: false } });
  } catch { res.json({ topic: { followed: false } }); }
});

export default router;
