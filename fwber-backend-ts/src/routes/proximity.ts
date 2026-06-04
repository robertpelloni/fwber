import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { FederationService } from '../services/FederationService.js';

const router = Router();
const federationService = new FederationService();

function safeProfile(u: any) {
  if (!u) return { name: 'Anonymous', avatar: null };
  const p = Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles;
  return { name: p?.display_name || u.name || 'Anonymous', avatar: p?.avatar_url || null };
}

// GET /api/proximity/local-pulse
router.get('/local-pulse', authenticate, async (req: any, res) => {
  try {
    const radius = Number(req.query.radius) || 5000;
    const artifacts = await prisma.proximity_artifacts.findMany({
      where: { moderation_status: 'clean', expires_at: { gt: new Date() } },
      orderBy: { created_at: 'desc' as const }, take: 30,
      include: { users: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } } }
    });
    res.json({
      artifacts: artifacts.map((a: any) => {
        const p = safeProfile(a.users);
        return { id: Number(a.id), type: a.type, content: a.content, lat: Number(a.location_lat), lng: Number(a.location_lng), radius_m: Number(a.visibility_radius_m), author_name: p.name, author_avatar: p.avatar, created_at: a.created_at?.toISOString(), expires_at: a.expires_at?.toISOString() };
      }),
      candidates: [], profiles: [], venues: [], total: artifacts.length, radius,
      meta: { artifacts_count: artifacts.length, candidates_count: 0, venues_count: 0, radius, ranking_strategy: 'freshness' }
    });
  } catch (err: any) { console.error('[proximity] error:', err.message); res.json({ artifacts: [], candidates: [], profiles: [], venues: [], total: 0, radius: 1000, meta: {} }); }
});

// GET /api/proximity/feed
router.get('/feed', authenticate, async (_req: any, res) => {
  try {
    const artifacts = await prisma.proximity_artifacts.findMany({
      where: { moderation_status: 'clean', expires_at: { gt: new Date() } },
      orderBy: { created_at: 'desc' as const }, take: 30,
      include: { users: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } } }
    });
    res.json({ artifacts: artifacts.map((a: any) => { const p = safeProfile(a.users); return { id: Number(a.id), type: a.type, content: a.content, author_name: p.name, author_avatar: p.avatar, created_at: a.created_at?.toISOString() }; }) });
  } catch { res.json({ artifacts: [] }); }
});

// POST /api/proximity/artifacts
router.post('/artifacts', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { type, content, lat, lng, radius_m } = req.body;
    if (!type || !content) return res.status(400).json({ error: 'type and content required' });
    const expiresAt = new Date(); expiresAt.setHours(expiresAt.getHours() + 4);
    const a = await prisma.proximity_artifacts.create({ data: { user_id: userId, type, content, location_lat: lat || 42.33, location_lng: lng || -83.05, visibility_radius_m: radius_m || 1000, moderation_status: 'clean', expires_at: expiresAt } });

    // Federation: Broadcast if enabled
    const profile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    if (profile?.is_federated) {
        federationService.broadcastUpdate(userId, {
            id: `https://api.fwber.me/api/proximity/artifacts/${a.id}`,
            type: 'Note',
            content: content,
            published: a.created_at?.toISOString()
        }).catch(err => console.error('[proximity] Federation broadcast failed:', err.message));
    }

    res.json({ artifact: { id: Number(a.id), type: a.type, content: a.content }, success: true });
  } catch (err: any) { console.error('[proximity] create error:', err.message); res.json({ artifact: null, success: false }); }
});

// GET /api/proximity/artifacts/:id
router.get('/artifacts/:id', authenticate, async (req: any, res) => {
  try {
    const a = await prisma.proximity_artifacts.findUnique({ where: { id: BigInt(req.params.id) }, include: { users: { select: { name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } } } });
    if (!a) return res.json({ artifact: null });
    const p = safeProfile(a.users);
    res.json({ artifact: { id: Number(a.id), type: a.type, content: a.content, author_name: p.name, author_avatar: p.avatar, created_at: a.created_at?.toISOString() } });
  } catch { res.json({ artifact: null }); }
});

// POST /api/proximity/artifacts/:id/flag
router.post('/artifacts/:id/flag', authenticate, async (req: any, res) => {
  await prisma.proximity_artifacts.update({ where: { id: BigInt(req.params.id) }, data: { moderation_status: 'flagged' } }).catch(() => {});
  res.json({ message: 'Flagged' });
});

// DELETE /api/proximity/artifacts/:id
router.delete('/artifacts/:id', authenticate, async (req: any, res) => {
  await prisma.proximity_artifacts.deleteMany({ where: { id: BigInt(req.params.id), user_id: BigInt(req.user.id) } }).catch(() => {});
  res.json({ message: 'Deleted' });
});

// POST /api/proximity/artifacts/:id/comments
router.post('/artifacts/:id/comments', authenticate, async (req: any, res) => {
  try {
    const c = await prisma.proximity_artifact_comments.create({ data: { proximity_artifact_id: BigInt(req.params.id), user_id: BigInt(req.user.id), content: req.body.content || '' } });
    res.json({ message: 'Comment added', comment: { id: Number(c.id) } });
  } catch { res.json({ message: 'Comment added', comment: null }); }
});

// GET /api/proximity/artifacts/:id/comments
router.get('/artifacts/:id/comments', authenticate, async (req: any, res) => {
  try {
    const comments = await prisma.proximity_artifact_comments.findMany({ where: { proximity_artifact_id: BigInt(req.params.id) }, orderBy: { created_at: 'asc' as const }, take: 50 });
    res.json({ data: comments.map((c: any) => ({ id: Number(c.id), content: c.content, created_at: c.created_at?.toISOString() })) });
  } catch { res.json({ data: [] }); }
});

// POST /api/proximity/artifacts/:id/vote
router.post('/artifacts/:id/vote', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const artifactId = BigInt(req.params.id);
    const val = Number(req.body.value) || 1;
    await prisma.proximity_artifact_votes.upsert({
      where: { proximity_artifact_id_user_id: { proximity_artifact_id: artifactId, user_id: userId } },
      update: { value: val },
      create: { proximity_artifact_id: artifactId, user_id: userId, value: val }
    }).catch(async () => {
      await prisma.proximity_artifact_votes.create({ data: { proximity_artifact_id: artifactId, user_id: userId, value: val } });
    });
    res.json({ message: 'Voted', vote: { value: val } });
  } catch { res.json({ message: 'Voted', vote: null }); }
});

export default router;
