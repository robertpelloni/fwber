import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

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

// GET /api/bulletin-boards — list topics as boards
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const page = Number(req.query.page) || 1;
    const perPage = Number(req.query.per_page) || 20;

    const topics = await prisma.topics.findMany({
      orderBy: { follower_count: 'desc' },
      skip: (page - 1) * perPage,
      take: perPage,
      include: {
        _count: { select: { topic_user_follows: true } },
      },
    });

    // Check which topics the user follows
    const follows = await prisma.topic_user_follows.findMany({
      where: { user_id: userId },
      select: { topic_id: true },
    });
    const followedIds = new Set(follows.map((f: any) => f.topic_id.toString()));

    const boards = topics.map((t: any) => ({
      id: Number(t.id),
      slug: t.slug,
      label: t.label,
      emoji: t.emoji,
      category: t.category,
      description: t.description,
      follower_count: t.follower_count || t._count?.topic_user_follows || 0,
      is_followed: followedIds.has(t.id.toString()),
    }));

    res.json({
      boards,
      meta: { total: topics.length, page, per_page: perPage },
    });
  } catch (error: any) {
    console.error('[BulletinBoards] List error:', error.message);
    res.json({ boards: [], meta: { total: 0 } });
  }
});

// GET /api/bulletin-boards/:slug — get topic details + posts
router.get('/:slug', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const slug = req.params.slug;

    const topic = await prisma.topics.findUnique({
      where: { slug },
    });

    if (!topic) {
      return res.status(404).json({ message: 'Topic not found' });
    }

    // Get proximity artifacts of type 'board_post' related to this topic
    const posts = await prisma.proximity_artifacts.findMany({
      where: { type: 'board_post', meta: { path: ['topic_slug'], equals: slug } },
      include: {
        users: {
          select: { id: true, name: true },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 20,
    });

    const isFollowed = await prisma.topic_user_follows.findFirst({
      where: { user_id: userId, topic_id: topic.id },
    });

    res.json(serialize({
      ...topic,
      is_followed: !!isFollowed,
      posts: posts.map((p: any) => ({
        id: Number(p.id),
        content: p.content,
        author: { id: Number(p.users?.id || 0), name: p.users?.name || 'Unknown' },
        created_at: p.created_at,
      })),
    }));
  } catch (error: any) {
    console.error('[BulletinBoards] Get error:', error.message);
    res.status(500).json({ message: 'Failed to fetch topic' });
  }
});

// POST /api/bulletin-boards/:slug/follow — follow a topic
router.post('/:slug/follow', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const slug = req.params.slug;

    const topic = await prisma.topics.findUnique({ where: { slug } });
    if (!topic) {
      return res.status(404).json({ message: 'Topic not found' });
    }

    await prisma.topic_user_follows.upsert({
      where: {
        topic_id_user_id: { topic_id: topic.id, user_id: userId },
      },
      create: { topic_id: topic.id, user_id: userId },
      update: {},
    });

    // Increment follower count
    await prisma.topics.update({
      where: { id: topic.id },
      data: { follower_count: { increment: 1 } },
    });

    res.json({ success: true });
  } catch (error: any) {
    console.error('[BulletinBoards] Follow error:', error.message);
    res.json({ success: true });
  }
});

// DELETE /api/bulletin-boards/:slug/follow — unfollow a topic
router.delete('/:slug/follow', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const slug = req.params.slug;

    const topic = await prisma.topics.findUnique({ where: { slug } });
    if (!topic) {
      return res.json({ success: true });
    }

    const deleted = await prisma.topic_user_follows.deleteMany({
      where: { topic_id: topic.id, user_id: userId },
    });

    if (deleted.count > 0) {
      await prisma.topics.update({
        where: { id: topic.id },
        data: { follower_count: { decrement: 1 } },
      });
    }

    res.json({ success: true });
  } catch (error: any) {
    res.json({ success: true });
  }
});

// POST /api/bulletin-boards — create a board post
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { topic_slug, content, latitude, longitude } = req.body;

    if (!content) {
      return res.status(400).json({ message: 'Content is required' });
    }

    const artifact = await prisma.proximity_artifacts.create({
      data: {
        user_id: userId,
        type: 'board_post',
        content,
        location_lat: latitude || 0,
        location_lng: longitude || 0,
        visibility_radius_m: 50000,
        moderation_status: 'clean',
        meta: { topic_slug: topic_slug || 'general' },
        expires_at: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 days
      },
    });

    res.json({ success: true, id: Number(artifact.id) });
  } catch (error: any) {
    console.error('[BulletinBoards] Post error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to create post' });
  }
});

export default router;
