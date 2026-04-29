import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/groups - List public groups
router.get('/', authenticate, async (req: any, res) => {
  try {
    const groups = await prisma.groups.findMany({
      where: { is_active: true },
      include: {
        users: { select: { id: true, name: true } },
        group_members: {
          where: { user_id: BigInt(req.user.id) },
          select: { role: true },
        },
      },
      orderBy: { member_count: 'desc' },
      take: 50,
    });

    const data = groups.map((g: any) => ({
      id: Number(g.id),
      name: g.name,
      description: g.description,
      icon: g.icon,
      privacy: g.privacy,
      member_count: g.member_count,
      created_by_user_id: Number(g.created_by_user_id),
      created_at: g.created_at?.toISOString(),
      updated_at: g.updated_at?.toISOString(),
      user_role: g.group_members?.[0]?.role || null,
      is_member: g.group_members?.length > 0,
    }));

    res.json({ data });
  } catch (err: any) {
    console.error('[GET /groups]', err.message);
    res.json({ data: [] });
  }
});

// GET /api/groups/my-groups
router.get('/my-groups', authenticate, async (req: any, res) => {
  try {
    const memberships = await prisma.group_members.findMany({
      where: { user_id: BigInt(req.user.id), is_banned: false },
      include: {
        groups: {
          include: {
            users: { select: { id: true, name: true } },
          },
        },
      },
    });

    const data = memberships.map((m: any) => ({
      id: Number(m.groups.id),
      name: m.groups.name,
      description: m.groups.description,
      icon: m.groups.icon,
      privacy: m.groups.privacy,
      member_count: m.groups.member_count,
      created_by_user_id: Number(m.groups.created_by_user_id),
      created_at: m.groups.created_at?.toISOString(),
      updated_at: m.groups.updated_at?.toISOString(),
      user_role: m.role,
      is_member: true,
    }));

    res.json({ data });
  } catch (err: any) {
    console.error('[GET /groups/my-groups]', err.message);
    res.json({ data: [] });
  }
});

// POST /api/groups - Create group
router.post('/', authenticate, async (req: any, res) => {
  try {
    const { name, description, icon, privacy, category, tags, matching_enabled, location_lat, location_lng } = req.body;

    if (!name) return res.status(400).json({ error: 'Name is required' });

    const group = await prisma.groups.create({
      data: {
        name,
        description: description || null,
        icon: icon || null,
        privacy: privacy || 'public',
        visibility: 'visible',
        created_by_user_id: BigInt(req.user.id),
        member_count: 1,
        is_active: true,
      },
    });

    // Add creator as owner
    await prisma.group_members.create({
      data: {
        group_id: group.id,
        user_id: BigInt(req.user.id),
        role: 'owner',
      },
    });

    res.json({
      id: Number(group.id),
      name: group.name,
      description: group.description,
      icon: group.icon,
      privacy: group.privacy,
      member_count: 1,
      created_by_user_id: Number(group.created_by_user_id),
      created_at: group.created_at?.toISOString(),
      updated_at: group.updated_at?.toISOString(),
      user_role: 'owner',
      is_member: true,
    });
  } catch (err: any) {
    console.error('[POST /groups]', err.message, err.stack);
    res.status(500).json({ error: 'Failed to create group' });
  }
});

// GET /api/groups/:id - Get single group
router.get('/:id', authenticate, async (req: any, res) => {
  try {
    const group = await prisma.groups.findUnique({
      where: { id: BigInt(req.params.id) },
      include: {
        users: { select: { id: true, name: true } },
        group_members: {
          select: { user_id: true, role: true },
        },
      },
    });
    if (!group) return res.status(404).json({ error: 'Group not found' });

    const myMembership = group.group_members.find((m: any) => m.user_id.toString() === req.user.id);

    res.json({
      id: Number(group.id),
      name: group.name,
      description: group.description,
      icon: group.icon,
      privacy: group.privacy,
      member_count: group.member_count,
      created_by_user_id: Number(group.created_by_user_id),
      created_at: group.created_at?.toISOString(),
      updated_at: group.updated_at?.toISOString(),
      user_role: myMembership?.role || null,
      is_member: !!myMembership,
      members: group.group_members.map((m: any) => ({
        user_id: Number(m.user_id),
        role: m.role,
      })),
    });
  } catch (err: any) {
    console.error('[GET /groups/:id]', err.message);
    res.status(500).json({ error: 'Failed to load group' });
  }
});

// POST /api/groups/:id/join
router.post('/:id/join', authenticate, async (req: any, res) => {
  try {
    await prisma.group_members.upsert({
      where: {
        group_id_user_id: { group_id: BigInt(req.params.id), user_id: BigInt(req.user.id) },
      },
      create: {
        group_id: BigInt(req.params.id),
        user_id: BigInt(req.user.id),
        role: 'member',
      },
      update: { is_banned: false, role: 'member' },
    });

    // Increment member count
    await prisma.groups.update({
      where: { id: BigInt(req.params.id) },
      data: { member_count: { increment: 1 } },
    });

    res.json({ message: 'Joined group', success: true });
  } catch (err: any) {
    console.error('[POST /groups/:id/join]', err.message);
    res.status(500).json({ error: 'Failed to join group' });
  }
});

// POST /api/groups/:id/leave
router.post('/:id/leave', authenticate, async (req: any, res) => {
  try {
    const deleted = await prisma.group_members.deleteMany({
      where: { group_id: BigInt(req.params.id), user_id: BigInt(req.user.id) },
    });

    if (deleted.count > 0) {
      await prisma.groups.update({
        where: { id: BigInt(req.params.id) },
        data: { member_count: { decrement: 1 } },
      });
    }

    res.json({ message: 'Left group', success: true });
  } catch (err: any) {
    console.error('[POST /groups/:id/leave]', err.message);
    res.json({ message: 'Left group', success: true });
  }
});

// GET /api/groups/:groupId/posts
router.get('/:groupId/posts', authenticate, async (req: any, res) => {
  try {
    const posts = await prisma.group_posts.findMany({
      where: { group_id: BigInt(req.params.groupId) },
      include: {
        users: { select: { id: true, name: true } },
      },
      orderBy: { created_at: 'desc' },
      take: 50,
    });
    res.json(posts.map((p: any) => ({
      id: Number(p.id),
      content: p.content,
      author: { id: Number(p.users?.id), name: p.users?.name },
      created_at: p.created_at?.toISOString(),
    })));
  } catch (err: any) {
    console.error('[GET /groups/:id/posts]', err.message);
    res.json([]);
  }
});

// POST /api/groups/:groupId/posts
router.post('/:groupId/posts', authenticate, async (req: any, res) => {
  try {
    const post = await prisma.group_posts.create({
      data: {
        group_id: BigInt(req.params.groupId),
        user_id: BigInt(req.user.id),
        content: req.body?.content || '',
      },
    });
    res.json({
      id: Number(post.id),
      content: post.content,
      author: { id: req.user.id, name: req.user.name },
      created_at: post.created_at?.toISOString(),
    });
  } catch (err: any) {
    console.error('[POST /groups/:id/posts]', err.message);
    res.status(500).json({ error: 'Failed to create post' });
  }
});

// DELETE /api/groups/posts/:postId
router.delete('/posts/:postId', authenticate, async (req: any, res) => {
  try {
    await prisma.group_posts.deleteMany({
      where: { id: BigInt(req.params.postId), user_id: BigInt(req.user.id) },
    });
    res.json({ message: 'Post deleted' });
  } catch (err: any) {
    console.error('[DELETE /groups/posts/:id]', err.message);
    res.json({ message: 'Post deleted' });
  }
});

// GET /api/groups/:groupId/matches - stub
router.get('/:groupId/matches', authenticate, async (req: any, res) => {
  res.json({ data: [], matches: [], meta: { total: 0 } });
});

// GET /api/groups/:groupId/matches/requests - stub
router.get('/:groupId/matches/requests', authenticate, async (req: any, res) => {
  res.json({ incoming: [], outgoing: [] });
});

// GET /api/groups/:groupId/matches/connected - stub
router.get('/:groupId/matches/connected', authenticate, async (req: any, res) => {
  res.json({ connected: [] });
});

// GET /api/groups/:groupId/events - stub
router.get('/:groupId/events', authenticate, async (req: any, res) => {
  res.json({ events: [] });
});

// POST /api/groups/:groupId/matching/toggle - stub
router.post('/:groupId/matching/toggle', authenticate, async (req: any, res) => {
  res.json({ matching_enabled: req.body?.enabled ?? true });
});

export default router;
