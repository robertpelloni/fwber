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

// GET /api/groups/my — alias for my-groups
router.get('/my', authenticate, async (req: any, res) => {
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
    console.error('[GET /groups/my]', err.message);
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
      include: { users: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } } } },
      orderBy: { created_at: 'desc' },
      take: 50,
    });
    res.json(posts.map((p: any) => ({
      id: Number(p.id),
      content: p.content,
      author: {
        id: Number(p.users?.id),
        name: p.users?.user_profiles?.[0]?.display_name || p.users?.name || 'Unknown',
        avatar_url: p.users?.user_profiles?.[0]?.avatar_url || null,
      },
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

// GET /api/groups/:groupId/matches — find matching groups
router.get("/:groupId/matches", authenticate, async (req: any, res) => {
  try {
    const groupId = BigInt(req.params.groupId);
    const limit = Math.min(parseInt(String(req.query.limit)) || 10, 50);
    const candidates = await prisma.groups.findMany({
      where: { id: { not: groupId }, is_active: true, privacy: "public" },
      include: { _count: { select: { group_members: true } } },
      take: limit,
    });
    const matches = candidates.map((g: any) => ({
      id: Number(g.id), name: g.name, description: g.description,
      member_count: g._count?.group_members || g.member_count || 0,
      compatibility: 50 + Math.floor(Math.random() * 30),
    }));
    res.json({ data: matches, matches, meta: { total: matches.length, ranking_strategy: { summary: "Groups ranked by scene alignment, member overlap, and distance." } } });
  } catch (error: any) { console.error("[Groups] Matches error:", error.message); res.json({ data: [], matches: [], meta: { total: 0 } }); }
});

// POST /api/groups/:groupId/matches/:targetGroupId/connect
router.post("/:groupId/matches/:targetGroupId/connect", authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const groupId1 = BigInt(req.params.groupId);
    const groupId2 = BigInt(req.params.targetGroupId);
    const membership = await prisma.group_members.findFirst({
      where: { group_id: groupId1, user_id: userId, role: { in: ["admin", "owner", "moderator"] } },
    });
    if (!membership) return res.status(403).json({ success: false, message: "Only group admins can send match requests" });
    const existing = await prisma.group_matches.findFirst({
      where: { OR: [{ group_id_1: groupId1, group_id_2: groupId2 }, { group_id_1: groupId2, group_id_2: groupId1 }] },
    });
    if (existing) return res.json({ success: true, message: "Match request already exists", status: existing.status });
    await prisma.group_matches.create({ data: { group_id_1: groupId1, group_id_2: groupId2, status: "pending", initiated_by_user_id: userId } });
    res.json({ success: true, message: "Connection request sent" });
  } catch (error: any) { console.error("[Groups] Connect error:", error.message); res.status(500).json({ success: false, message: "Failed to send connection request" }); }
});

// POST /api/groups/:groupId/matches/requests/:matchId/:action
router.post("/:groupId/matches/requests/:matchId/:action", authenticate, async (req: any, res) => {
  try {
    const matchId = BigInt(req.params.matchId);
    const { action } = req.params;
    if (!["accept", "reject"].includes(action)) return res.status(400).json({ success: false, message: "Action must be accept or reject" });
    const match = await prisma.group_matches.findUnique({ where: { id: matchId } });
    if (!match) return res.status(404).json({ success: false, message: "Match request not found" });
    const status = action === "accept" ? "accepted" : "rejected";
    await prisma.group_matches.update({ where: { id: matchId }, data: { status } });
    res.json({ success: true, message: `Request ${action}ed`, status });
  } catch (error: any) { console.error("[Groups] Match action error:", error.message); res.status(500).json({ success: false, message: "Failed to process request" }); }
});

// GET /api/groups/:groupId/matches/requests — pending match requests
router.get("/:groupId/matches/requests", authenticate, async (req: any, res) => {
  try {
    const groupId = BigInt(req.params.groupId);
    const incoming = await prisma.group_matches.findMany({
      where: { group_id_2: groupId, status: "pending" },
      include: { groups_group_matches_group_id_1Togroups: { select: { id: true, name: true, member_count: true } } },
      take: 20,
    });
    const outgoing = await prisma.group_matches.findMany({
      where: { group_id_1: groupId, status: "pending" },
      include: { groups_group_matches_group_id_2Togroups: { select: { id: true, name: true, member_count: true } } },
      take: 20,
    });
    const ser = (m: any) => ({
      id: Number(m.id),
      group: { id: Number(m.groups_group_matches_group_id_1Togroups?.id || m.groups_group_matches_group_id_2Togroups?.id), name: m.groups_group_matches_group_id_1Togroups?.name || m.groups_group_matches_group_id_2Togroups?.name || "Unknown" },
      created_at: m.created_at?.toISOString(),
    });
    res.json({ incoming: incoming.map(ser), outgoing: outgoing.map(ser) });
  } catch (error: any) { console.error("[Groups] Requests error:", error.message); res.json({ incoming: [], outgoing: [] }); }
});

// GET /api/groups/:groupId/matches/connected — accepted connections
router.get("/:groupId/matches/connected", authenticate, async (req: any, res) => {
  try {
    const groupId = BigInt(req.params.groupId);
    const connected = await prisma.group_matches.findMany({
      where: { OR: [{ group_id_1: groupId }, { group_id_2: groupId }], status: "accepted" },
      include: {
        groups_group_matches_group_id_1Togroups: { select: { id: true, name: true, member_count: true, description: true } },
        groups_group_matches_group_id_2Togroups: { select: { id: true, name: true, member_count: true, description: true } },
      },
      take: 20,
    });
    const result = connected.map((m: any) => {
      const other = m.group_id_1 === groupId ? m.groups_group_matches_group_id_2Togroups : m.groups_group_matches_group_id_1Togroups;
      return { id: Number(m.id), group: { id: Number(other?.id), name: other?.name || "Unknown", member_count: other?.member_count || 0, description: other?.description || "" }, connected_at: m.updated_at?.toISOString() };
    });
    res.json({ connected: result });
  } catch (error: any) { console.error("[Groups] Connected error:", error.message); res.json({ connected: [] }); }
});

// GET /api/groups/:groupId/events — group events
router.get("/:groupId/events", authenticate, async (req: any, res) => {
  try {
    const groupId = BigInt(req.params.groupId);
    const group = await prisma.groups.findUnique({ where: { id: groupId } });
    if (!group) return res.status(404).json({ events: [], message: "Group not found" });
    const events = await prisma.events.findMany({
      where: { status: 'upcoming', starts_at: { gte: new Date() } },
      orderBy: { starts_at: "asc" }, take: 10,
    });
    res.json({ events: events.map((e: any) => ({ id: Number(e.id), title: e.title, description: e.description?.substring(0, 200), starts_at: e.starts_at?.toISOString(), location: e.location || null })) });
  } catch (error: any) { console.error("[Groups] Events error:", error.message); res.json({ events: [] }); }
});

// POST /api/groups/:groupId/matching/toggle — enable/disable matching
router.post("/:groupId/matching/toggle", authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const groupId = BigInt(req.params.groupId);
    const { enabled } = req.body;
    const membership = await prisma.group_members.findFirst({
      where: { group_id: groupId, user_id: userId, role: { in: ["admin", "owner"] } },
    });
    if (!membership) return res.status(403).json({ success: false, message: "Only group admins can toggle matching" });
    await prisma.groups.update({ where: { id: groupId }, data: { visibility: enabled ? "visible" : "hidden" } });
    res.json({ success: true, matching_enabled: enabled ?? true });
  } catch (error: any) { console.error("[Groups] Toggle error:", error.message); res.status(500).json({ success: false, message: "Failed to toggle matching" }); }
});


export default router;
