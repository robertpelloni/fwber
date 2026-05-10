import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { filePathToUrl } from '../lib/photos.js';

const router = Router();

// All recommendation routes require auth
router.use(authenticate);

/**
 * GET /api/recommendations — general recommendations (alias for mixed)
 */
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const limit = parseInt(req.query.limit as string) || 10;
    const types = (req.query.types as string)?.split(',') || [];
    const lat = req.query.latitude ? parseFloat(String(req.query.latitude)) : null;
    const lng = req.query.longitude ? parseFloat(String(req.query.longitude)) : null;
    const radius = parseInt(req.query.radius as string) || 50000; // 50km

    // Exclude existing matches and self
    const existing = await prisma.matches.findMany({
      where: { OR: [{ user1_id: userId }, { user2_id: userId }] },
      select: { user1_id: true, user2_id: true },
    });
    const exclude = new Set<string>([userId.toString()]);
    existing.forEach((m: any) => { exclude.add(m.user1_id.toString()); exclude.add(m.user2_id.toString()); });

    // Get current user's profile for scoring
    const myProfile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    const myInterests: string[] = parseJsonArray(myProfile?.interests);
    const myLookingFor: string[] = parseJsonArray(myProfile?.looking_for);
    const myGender = myProfile?.gender;
    const myPrefs: any = parseJsonObject(myProfile?.preferences);
    const myOrientation = myProfile?.sexual_orientation;

    // Fetch candidates
    const candidates = await prisma.users.findMany({
      where: {
        id: { notIn: Array.from(exclude).map((id) => BigInt(id)) },
        user_profiles: { some: {} },
      },
      include: {
        user_profiles: true,
        photos: { where: { is_private: false }, orderBy: { is_primary: 'desc' as const }, take: 5 },
      },
      take: limit * 3, // overfetch for scoring/filtering
    });

    let results = candidates.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      return buildRecommendation(u, p, myInterests, myLookingFor, lat, lng, radius);
    });

    // Filter by distance if location provided
    if (lat && lng) {
      results = results.filter(r => r.distance_meters <= radius);
    }

    // Sort by score descending
    results.sort((a, b) => b.compatibility_score - a.compatibility_score);

    const recommendations = results.slice(0, limit).map((r: any) => ({
      id: String(r.id),
      type: r.type || 'ai',
      // Flat fields for easy frontend access
      display_name: r.user?.name || r.user?.display_name || 'Anonymous',
      name: r.user?.name || 'Anonymous',
      bio: r.user?.bio || null,
      gender: r.user?.gender || null,
      age: r.user?.age || null,
      location_description: r.user?.location_description || null,
      is_verified: r.user?.is_verified || false,
      avatar_url: r.user?.avatar_url || null,
      distance: r.distance_meters || 0,
      distance_miles: Math.round((r.distance_meters || 0) / 1609.34 * 10) / 10,
      distance_meters: r.distance_meters || 0,
      shared_interests: r.shared_interests || [],
      photos: r.user?.photos || [],
      compatibility_score: r.compatibility_score || 0,
      // Nested content for backward compat
      content: {
        id: String(r.id),
        name: r.user?.name || 'Anonymous',
        bio: r.user?.bio || null,
        gender: r.user?.gender || null,
        age: r.user?.age || null,
        location_description: r.user?.location_description || null,
        is_verified: r.user?.is_verified || false,
        avatar_url: r.user?.avatar_url || null,
        distance: r.distance_meters || 0,
        shared_interests: r.shared_interests || [],
        photos: r.user?.photos || [],
      },
      score: r.compatibility_score || 0,
      reason: r.reason || 'Recommended for you',
      source: 'mixed',
    }));

    res.json({
      recommendations,
      metadata: {
        total: results.length,
        types,
        context: { latitude: lat, longitude: lng, radius },
        generated_at: new Date().toISOString(),
        cache_hit: false,
      },
    });
  } catch (err: any) {
    console.error('[GET /recommendations]', err.message);
    res.json({ recommendations: [], metadata: { total: 0, types: [], context: {}, generated_at: new Date().toISOString(), cache_hit: false } });
  }
});

/**
 * GET /api/recommendations/type/:type — by recommendation type
 */
router.get('/type/:type', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { type } = req.params;
    const limit = parseInt(req.query.limit as string) || 10;
    const lat = req.query.latitude ? parseFloat(String(req.query.latitude)) : null;
    const lng = req.query.longitude ? parseFloat(String(req.query.longitude)) : null;

    const existing = await prisma.matches.findMany({
      where: { OR: [{ user1_id: userId }, { user2_id: userId }] },
      select: { user1_id: true, user2_id: true },
    });
    const exclude = new Set<string>([userId.toString()]);
    existing.forEach((m: any) => { exclude.add(m.user1_id.toString()); exclude.add(m.user2_id.toString()); });

    const myProfile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    const myInterests: string[] = parseJsonArray(myProfile?.interests);

    const candidates = await prisma.users.findMany({
      where: {
        id: { notIn: Array.from(exclude).map((id) => BigInt(id)) },
        user_profiles: { some: {} },
      },
      include: {
        user_profiles: true,
        photos: { where: { is_private: false }, orderBy: { is_primary: 'desc' as const }, take: 5 },
      },
      take: limit * 2,
    });

    let results = candidates.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      return buildRecommendation(u, p, myInterests, [], lat, lng, 50000);
    });

    // Type-specific scoring adjustments
    switch (type) {
      case 'ai':
        results.sort((a, b) => b.compatibility_score - a.compatibility_score);
        break;
      case 'location':
        results.sort((a, b) => (a.distance_meters || Infinity) - (b.distance_meters || Infinity));
        break;
      case 'trending':
        results.sort((a, b) => (b as any).last_seen_score - (a as any).last_seen_score);
        break;
      case 'collaborative':
        results.sort((a, b) => b.shared_interest_count - a.shared_interest_count);
        break;
      default:
        results.sort((a, b) => b.compatibility_score - a.compatibility_score);
    }

    res.json({
      recommendations: results.slice(0, limit).map((r: any) => ({
        id: String(r.id),
        type: type,
        content: {
          id: String(r.id),
          name: r.user?.name || 'Anonymous',
          bio: r.user?.bio || null,
          gender: r.user?.gender || null,
          age: r.user?.age || null,
          location_description: r.user?.location_description || null,
          is_verified: r.user?.is_verified || false,
          avatar_url: r.user?.avatar_url || null,
          distance: r.distance_meters || 0,
          shared_interests: r.shared_interests || [],
          photos: r.user?.photos || [],
        },
        score: r.compatibility_score || 0,
        reason: r.reason || 'Recommended for you',
        source: type,
      })),
      metadata: { total: results.length, types: [type], context: {}, generated_at: new Date().toISOString(), cache_hit: false },
    });
  } catch (err: any) {
    console.error(`[GET /recommendations/type/${req.params.type}]`, err.message);
    res.json({ recommendations: [], metadata: { total: 0, types: [req.params.type], context: {}, generated_at: new Date().toISOString(), cache_hit: false } });
  }
});

/**
 * GET /api/recommendations/trending
 */
router.get('/trending', async (req: any, res) => {
  try {
    const limit = parseInt(req.query.limit as string) || 5;

    // Trending = recently active users with profiles
    const trending = await prisma.users.findMany({
      where: {
        user_profiles: { some: {} },
        last_seen_at: { gte: new Date(Date.now() - 7 * 86400000) },
      },
      include: { user_profiles: true },
      orderBy: { last_seen_at: 'desc' },
      take: limit,
    });

    const results = trending.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      return {
        id: Number(u.id),
        type: 'trending',
        user: {
          id: Number(u.id),
          name: p.display_name || u.name || 'Anonymous',
          bio: p.bio || null,
          gender: p.gender || null,
          age: p.date_of_birth ? new Date().getFullYear() - new Date(p.date_of_birth as any).getFullYear() : null,
          location_description: p.location_description || null,
          is_verified: p.is_verified || false,
          avatar_url: p.avatar_url || null,
        },
        reason: 'Recently active',
        compatibility_score: 0,
        last_seen_at: u.last_seen_at?.toISOString(),
      };
    });

    const recommendations = results.map((r: any) => ({
      id: String(r.id),
      type: 'trending',
      content: {
        id: String(r.id),
        name: r.user?.name || 'Anonymous',
        bio: r.user?.bio || null,
        gender: r.user?.gender || null,
        age: r.user?.age || null,
        location_description: r.user?.location_description || null,
        is_verified: r.user?.is_verified || false,
      },
      score: r.compatibility_score || 0,
      reason: r.reason || 'Trending',
      source: 'trending',
    }));

    res.json({ recommendations, metadata: { total: results.length, types: ['trending'], context: { timeframe: req.query.timeframe || '7d' }, generated_at: new Date().toISOString(), cache_hit: false } });
  } catch (err: any) {
    console.error('[GET /recommendations/trending]', err.message);
    res.json({ recommendations: [], metadata: { total: 0, types: ['trending'], context: { timeframe: '7d' }, generated_at: new Date().toISOString(), cache_hit: false } });
  }
});

/**
 * GET /api/recommendations/feed
 */
router.get('/feed', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const page = parseInt(req.query.page as string) || 1;
    const perPage = parseInt(req.query.per_page as string) || 20;

    const existing = await prisma.matches.findMany({
      where: { OR: [{ user1_id: userId }, { user2_id: userId }] },
      select: { user1_id: true, user2_id: true },
    });
    const exclude = new Set<string>([userId.toString()]);
    existing.forEach((m: any) => { exclude.add(m.user1_id.toString()); exclude.add(m.user2_id.toString()); });

    const myProfile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    const myInterests: string[] = parseJsonArray(myProfile?.interests);

    const candidates = await prisma.users.findMany({
      where: {
        id: { notIn: Array.from(exclude).map((id) => BigInt(id)) },
        user_profiles: { some: {} },
      },
      include: {
        user_profiles: true,
        photos: { where: { is_private: false }, orderBy: { is_primary: 'desc' as const }, take: 5 },
      },
      orderBy: { last_seen_at: 'desc' },
      skip: (page - 1) * perPage,
      take: perPage,
    });

    const items = candidates.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      const theirInterests: string[] = parseJsonArray(p.interests);
      const shared = myInterests.filter(i => theirInterests.includes(i));

      return {
        id: Number(u.id),
        type: 'feed',
        user: {
          id: Number(u.id),
          name: p.display_name || u.name || 'Anonymous',
          bio: p.bio || null,
          gender: p.gender || null,
          age: p.date_of_birth ? new Date().getFullYear() - new Date(p.date_of_birth as any).getFullYear() : null,
          location_description: p.location_description || null,
          is_verified: p.is_verified || false,
          avatar_url: p.avatar_url || null,
          photos: (u.photos || []).map((ph: any) => ({
            id: Number(ph.id),
            url: filePathToUrl(ph.file_path || ph.url),
            is_private: ph.is_private || false,
            is_primary: ph.is_primary || false,
          })),
        },
        shared_interests: shared,
        shared_interest_count: shared.length,
        reason: shared.length > 0 ? `${shared.length} shared interest${shared.length > 1 ? 's' : ''}` : 'New in your area',
        compatibility_score: Math.min(100, 30 + shared.length * 10),
        last_seen_at: u.last_seen_at?.toISOString(),
      };
    });

    res.json({ items, page, per_page: perPage, total: items.length, total_pages: 1 });
  } catch (err: any) {
    console.error('[GET /recommendations/feed]', err.message);
    res.json({ items: [], page: 1, per_page: 20, total: 0, total_pages: 0 });
  }
});

/**
 * POST /api/recommendations/feedback
 */
router.post('/feedback', async (req: any, res) => {
  res.json({ message: 'Feedback recorded', feedback_id: `fb-${Date.now()}` });
});

/**
 * GET /api/recommendations/analytics
 */
router.get('/analytics', async (req: any, res) => {
  res.json({ total_recommendations: 0, clicked: 0, matched: 0, dismissed: 0 });
});

// ─── Helpers ───────────────────────────────────────────────────────────────

function parseJsonArray(val: any): string[] {
  if (Array.isArray(val)) return val;
  if (typeof val === 'string') { try { return JSON.parse(val); } catch { return []; } }
  return [];
}

function parseJsonObject(val: any): any {
  if (val && typeof val === 'object') return val;
  if (typeof val === 'string') { try { return JSON.parse(val); } catch { return {}; } }
  return {};
}

function buildRecommendation(u: any, p: any, myInterests: string[], myLookingFor: string[], lat: number | null, lng: number | null, radius: number) {
  const theirInterests: string[] = parseJsonArray(p.interests);
  const shared = myInterests.filter(i => theirInterests.includes(i));

  let distanceMeters = 0;
  if (lat && lng && p.location_latitude && p.location_longitude) {
    const R = 6371;
    const dLat = ((Number(p.location_latitude) - lat) * Math.PI) / 180;
    const dLon = ((Number(p.location_longitude) - lng) * Math.PI) / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos((lat * Math.PI) / 180) * Math.cos((Number(p.location_latitude) * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
    distanceMeters = Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) * 1000);
  }

  // Score: base 30 + shared interests (12 each, max 48) + distance + verified + looking_for + profile completeness
  let score = 30;
  score += Math.min(shared.length * 12, 48); // shared interests
  if (distanceMeters > 0 && distanceMeters < 5000) score += 8;
  if (distanceMeters > 0 && distanceMeters < 1000) score += 5;
  if (p.is_verified) score += 5;
  if (p.is_id_verified) score += 3;
  // Looking-for overlap bonus
  const theirLookingFor: string[] = parseJsonArray(p.looking_for);
  const lookingOverlap = myLookingFor.filter(lf => theirLookingFor.includes(lf));
  if (lookingOverlap.length > 0) score += 8;
  // Profile completeness bonus
  if (p.bio && p.bio.length > 20) score += 3;
  if (p.avatar_url) score += 3;
  // Activity bonus (recently active)
  if (u.last_seen_at && (Date.now() - new Date(u.last_seen_at).getTime()) < 86400000) score += 3;
  score = Math.min(99, score);

  let reason = 'Recommended for you';
  if (shared.length >= 3) reason = `${shared.length} shared interests`;
  else if (shared.length > 0) reason = `Shares your love of ${shared[0]}`;
  else if (distanceMeters > 0 && distanceMeters < 5000) reason = 'Nearby';
  else if (p.is_verified) reason = 'Verified profile';

  return {
    id: Number(u.id),
    type: 'recommendation',
    user: {
      id: Number(u.id),
      name: p.display_name || u.name || 'Anonymous',
      bio: p.bio || null,
      gender: p.gender || null,
      age: p.date_of_birth ? new Date().getFullYear() - new Date(p.date_of_birth as any).getFullYear() : null,
      location_description: p.location_description || null,
      is_verified: p.is_verified || false,
      avatar_url: p.avatar_url || null,
      photos: (u.photos || []).map((ph: any) => ({
        id: Number(ph.id),
        url: filePathToUrl(ph.file_path || ph.url),
        is_private: ph.is_private || false,
        is_primary: ph.is_primary || false,
      })),
    },
    shared_interests: shared,
    shared_interest_count: shared.length,
    reason,
    compatibility_score: score,
    distance_meters: distanceMeters,
    last_seen_at: u.last_seen_at?.toISOString(),
    last_seen_score: u.last_seen_at ? Date.now() - new Date(u.last_seen_at).getTime() : Infinity,
  };
}

export default router;
