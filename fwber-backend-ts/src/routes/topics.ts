import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Helper to serialize a topic with all frontend-expected fields
async function serializeTopic(t: any, userId: bigint | null): Promise<any> {
  let isFollowed = false;
  if (userId) {
    try {
      const follow = await prisma.topic_user_follows.findFirst({
        where: { user_id: userId, topic_id: t.id },
      });
      isFollowed = !!follow;
    } catch { /* ignore */ }
  }

  // Get group count for this topic
  let groupCount = 0;
  try {
    const gcRows: any[] = await prisma.$queryRawUnsafe(
      `SELECT COUNT(DISTINCT gt.group_id) as cnt FROM group_topics gt WHERE gt.topic_id = ?`,
      t.id.toString()
    );
    if (gcRows.length > 0) groupCount = Number(gcRows[0].cnt);
  } catch { /* group_topics may not exist */ }

  return {
    id: Number(t.id),
    slug: t.slug,
    label: t.label,
    emoji: t.emoji || null,
    category: t.category || null,
    description: t.description || null,
    aliases: [],
    is_featured: Number(t.follower_count) >= 5,
    sort_order: 0,
    follower_count: Number(t.follower_count),
    group_count: groupCount,
    journal_count: 0,
    artifact_count: 0,
    is_followed: isFollowed,
  };
}

// GET /api/topics — list all topics with filters
// Public: featured topic listing works without auth
router.get('/', async (req: any, res) => {
  try {
    let userId: bigint | null = null;
    try {
      if (req.headers.authorization) {
        const token = req.headers.authorization.split(' ')[1];
        const { default: jwt } = await import('jsonwebtoken');
        const decoded: any = jwt.verify(token, process.env.JWT_SECRET || 'secret');
        userId = BigInt(decoded.id);
      }
    } catch { /* not authenticated */ }
    const search = String(req.query.search || '').trim();
    const category = String(req.query.category || '').trim();
    const featured = req.query.featured === 'true';
    const followedOnly = req.query.followed === 'true';

    const where: any = {};
    if (search) {
      where.OR = [
        { label: { contains: search } },
        { slug: { contains: search } },
        { category: { contains: search } },
      ];
    }
    if (category) where.category = category;
    if (featured) where.follower_count = { gte: 5 };

    let topics: any[];
    if (followedOnly) {
      const follows = await prisma.topic_user_follows.findMany({
        where: { user_id: userId },
        include: { topics: true },
      });
      topics = follows.map((f: any) => f.topics);
    } else {
      topics = await prisma.topics.findMany({
        where,
        orderBy: { follower_count: 'desc' as const },
      });
    }

    const serialized = [];
    for (const t of topics) {
      serialized.push(await serializeTopic(t, userId));
    }

    res.json({ topics: serialized, total: serialized.length });
  } catch (err: any) {
    console.error('[topics] GET error:', err.message);
    res.json({ topics: [], total: 0 });
  }
});

// GET /api/topics/featured — popular topics (public)
router.get('/featured', async (req: any, res) => {
  try {
    let userId: bigint | null = null;
    try {
      if (req.headers.authorization) {
        const token = req.headers.authorization.split(' ')[1];
        const { default: jwt } = await import('jsonwebtoken');
        const decoded: any = jwt.verify(token, process.env.JWT_SECRET || 'secret');
        userId = BigInt(decoded.id);
      }
    } catch { /* not authenticated */ }
    const topics = await prisma.topics.findMany({
      where: { follower_count: { gte: 5 } },
      orderBy: { follower_count: 'desc' as const },
      take: 10,
    });
    const serialized = [];
    for (const t of topics) {
      serialized.push(await serializeTopic(t, userId));
    }
    res.json({ topics: serialized, total: serialized.length });
  } catch {
    res.json({ topics: [], total: 0 });
  }
});

// GET /api/topics/followed — user's followed topics
router.get('/followed', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const follows = await prisma.topic_user_follows.findMany({
      where: { user_id: userId },
      include: { topics: true },
    });
    const serialized = [];
    for (const f of follows) {
      serialized.push(await serializeTopic(f.topics, userId));
    }
    res.json({ topics: serialized });
  } catch (err: any) {
    console.error('[topics] followed error:', err.message);
    res.json({ topics: [] });
  }
});

// GET /api/topics/trending — trending topics by follower count
router.get('/trending', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const limit = Math.min(Number(req.query.limit) || 20, 50);
    const topics = await prisma.topics.findMany({
      orderBy: { follower_count: 'desc' as const },
      take: limit,
    });
    const serialized = [];
    for (const t of topics) {
      serialized.push(await serializeTopic(t, userId));
    }
    res.json({ topics: serialized, total: serialized.length });
  } catch (err: any) {
    console.error('[topics] trending error:', err.message);
    res.json({ topics: [], total: 0 });
  }
});

// GET /api/topics/search — search topics
router.get('/search', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const q = String(req.query.q || '');
    if (!q) return res.json({ topics: [], total: 0 });
    const topics = await prisma.topics.findMany({
      where: {
        OR: [
          { label: { contains: q } },
          { slug: { contains: q } },
          { category: { contains: q } },
        ],
      },
      orderBy: { follower_count: 'desc' as const },
      take: 20,
    });
    const serialized = [];
    for (const t of topics) {
      serialized.push(await serializeTopic(t, userId));
    }
    res.json({ topics: serialized, total: serialized.length });
  } catch (err: any) {
    console.error('[topics] search error:', err.message);
    res.json({ topics: [], total: 0 });
  }
});

// GET /api/topics/:slug — single topic with detail
router.get('/:slug', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const topic = await prisma.topics.findUnique({
      where: { slug: req.params.slug },
    });
    if (!topic) return res.json({ topic: null });

    const serialized = await serializeTopic(topic, userId);

    // Get related groups
    let groups: any[] = [];
    try {
      const groupRows: any[] = await prisma.$queryRawUnsafe(
        `SELECT g.id, g.name, g.description, g.is_public
         FROM \`groups\` g
         JOIN group_topics gt ON gt.group_id = g.id
         WHERE gt.topic_id = ?
         LIMIT 10`,
        topic.id.toString()
      );
      groups = groupRows.map((r: any) => ({
        id: Number(r.id),
        name: r.name,
        description: r.description,
        is_public: Boolean(r.is_public),
      }));
    } catch { /* group_topics may not exist */ }

    res.json({ topic: serialized, groups, journals: [], artifacts: [] });
  } catch {
    res.json({ topic: null });
  }
});

// POST /api/topics/:slug/follow
router.post('/:slug/follow', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const topic = await prisma.topics.findUnique({
      where: { slug: req.params.slug },
    });
    if (!topic) return res.status(404).json({ error: 'Topic not found' });

    try {
      await prisma.topic_user_follows.create({
        data: { user_id: userId, topic_id: topic.id },
      });
    } catch { /* already following */ }

    await prisma.topics.update({
      where: { id: topic.id },
      data: { follower_count: { increment: 1 } },
    }).catch(() => {});

    const serialized = await serializeTopic(topic, userId);
    res.json({ topic: { ...serialized, is_followed: true } });
  } catch (err: any) {
    console.error('[topics] follow error:', err.message);
    res.json({ topic: { followed: true } });
  }
});

// DELETE /api/topics/:slug/follow
router.delete('/:slug/follow', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const topic = await prisma.topics.findUnique({
      where: { slug: req.params.slug },
    });
    if (!topic) return res.status(404).json({ error: 'Topic not found' });

    await prisma.topic_user_follows.deleteMany({
      where: { user_id: userId, topic_id: topic.id },
    });

    await prisma.topics.update({
      where: { id: topic.id },
      data: { follower_count: { decrement: 1 } },
    }).catch(() => {});

    const serialized = await serializeTopic(topic, userId);
    res.json({ topic: { ...serialized, is_followed: false } });
  } catch {
    res.json({ topic: { followed: false } });
  }
});

export default router;
