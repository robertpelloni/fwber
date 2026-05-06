import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Helper: serialize BigInt
function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (obj instanceof Date) return obj;
  if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

/**
 * Calculate a composite activity score for ranking.
 * Based on: matches made, messages sent, profile completeness, and days active.
 */
async function buildLeaderboard(userId: bigint | null, type: string, page: number, perPage: number) {
  // Get users with their profiles
  const users = await prisma.users.findMany({
    where: { role: 'user' },
    include: {
      user_profiles: {
        select: {
          display_name: true,
          avatar_url: true,
          bio: true,
          gender: true,
          date_of_birth: true,
          location_name: true,
          interests: true,
        },
        take: 1,
      },
    },
    take: 100,
  });

  console.log('[Leaderboard] Found', users.length, 'users');
  // Batch: count matches per user in one query
  const userIds = users.map(u => u.id);
  const matchCounts = new Map<string, number>();
  const messageCounts = new Map<string, number>();

  try {
    const matchGrouped = await prisma.matches.groupBy({
      by: ['user1_id'],
      where: { user1_id: { in: userIds }, is_active: true },
      _count: { id: true },
    });
    for (const g of matchGrouped) {
      matchCounts.set(g.user1_id.toString(), g._count.id);
    }
    // Also count as user2
    const matchGrouped2 = await prisma.matches.groupBy({
      by: ['user2_id'],
      where: { user2_id: { in: userIds }, is_active: true },
      _count: { id: true },
    });
    for (const g of matchGrouped2) {
      const existing = matchCounts.get(g.user2_id.toString()) || 0;
      matchCounts.set(g.user2_id.toString(), existing + g._count.id);
    }
  } catch (_) {}

  try {
    const msgGrouped = await prisma.messages.groupBy({
      by: ['sender_id'],
      where: { sender_id: { in: userIds } },
      _count: { id: true },
    });
    for (const g of msgGrouped) {
      messageCounts.set(g.sender_id.toString(), g._count.id);
    }
  } catch (_) {}

  // Calculate scores
  const scored = [];
  for (const user of users) {
    const profile = Array.isArray(user.user_profiles)
      ? user.user_profiles[0]
      : user.user_profiles;

    const matchCount = matchCounts.get(user.id.toString()) || 0;
    const messageCount = messageCounts.get(user.id.toString()) || 0;

    let completeness = 0;
    if (profile?.gender) completeness++;
    if (profile?.bio && profile.bio.length > 10) completeness++;
    if (profile?.date_of_birth) completeness++;
    if (profile?.location_name) completeness++;
    if (profile?.interests) completeness++;

    const score = (matchCount * 10) + (messageCount * 2) + (completeness * 5) +
      (user.created_at ? Math.max(0, 30 - Math.floor((Date.now() - new Date(user.created_at).getTime()) / (86400000))) : 0);

    scored.push({
      user_id: Number(user.id),
      name: profile?.display_name || user.name || 'Anonymous',
      avatar_url: profile?.avatar_url || null,
      score,
      matches: matchCount,
      messages: messageCount,
      completeness,
      rank: 0,
    });
  }

  console.log('[Leaderboard] Scored', scored.length, 'users, top score:', scored[0]?.score || 0);
  scored.sort((a, b) => b.score - a.score);
  scored.forEach((entry, index) => { entry.rank = index + 1; });

  const total = scored.length;
  const start = (page - 1) * perPage;
  const paginated = scored.slice(start, start + perPage);

  return { leaderboard: paginated, total, page, per_page: perPage, type };
}

// GET /api/leaderboard - Get leaderboard rankings
router.get('/', authenticate, async (req: any, res) => {
  try {
    const { type = 'global', page = 1, per_page = 20 } = req.query;
    const userId = BigInt(req.user.id);

    const result: any = await buildLeaderboard(userId, String(type), Number(page), Number(per_page));

    // Add current user's rank
    const userEntry = result.leaderboard.find(
      (e: any) => e.user_id === Number(userId)
    );
    if (!userEntry) {
      const allRanked = result.leaderboard;
      // Find user in full list
      const fullResult = await buildLeaderboard(userId, String(type), 1, 200);
      const userInFull = fullResult.leaderboard.find(
        (e: any) => e.user_id === Number(userId)
      );
      result['my_rank'] = userInFull ? userInFull.rank : null;
    } else {
      result['my_rank'] = userEntry.rank;
    }

    res.json(serialize(result));
  } catch (error: any) {
    console.error('[Leaderboard] Error:', error.message, error.stack);
    res.json({ leaderboard: [], total: 0, page: 1, per_page: 20, type: 'global', error: error.message });
  }
});

// GET /api/leaderboard/nearby - Get nearby leaderboard
router.get('/nearby', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Get user's location
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: userId },
      select: { location_latitude: true, location_longitude: true },
    });

    if (!profile?.location_latitude || !profile?.location_longitude) {
      return res.json({ leaderboard: [], total: 0, message: 'Set your location to see nearby rankings' });
    }

    const userLat = Number(profile.location_latitude);
    const userLng = Number(profile.location_longitude);

    // Get nearby users with profiles
    const nearbyUsers = await prisma.users.findMany({
      where: { role: 'user' },
      include: {
        user_profiles: {
          select: {
            display_name: true,
            avatar_url: true,
            location_latitude: true,
            location_longitude: true,
            bio: true,
            gender: true,
            interests: true,
          },
          take: 1,
        },
      },
      take: 100,
    });

    // Score and filter by distance
    const scored = [];
    for (const user of nearbyUsers) {
      const p = Array.isArray(user.user_profiles) ? user.user_profiles[0] : user.user_profiles;
      if (!p?.location_latitude || !p?.location_longitude) continue;

      const lat = Number(p.location_latitude);
      const lng = Number(p.location_longitude);
      const distKm = Math.sqrt(
        Math.pow((lat - userLat) * 111, 2) +
        Math.pow((lng - userLng) * 111 * Math.cos(userLat * Math.PI / 180), 2)
      );

      if (distKm > 100) continue; // Within 100km

      const matchCount = await prisma.matches.count({
        where: {
          OR: [{ user1_id: user.id }, { user2_id: user.id }],
          is_active: true,
        },
      });

      scored.push({
        user_id: Number(user.id),
        name: p?.display_name || user.name || 'Anonymous',
        avatar_url: p?.avatar_url || null,
        score: matchCount * 10,
        matches: matchCount,
        distance_km: Math.round(distKm * 10) / 10,
      rank: 0,
      });
    }

    console.log('[Leaderboard] Scored', scored.length, 'users, top score:', scored[0]?.score || 0);
  scored.sort((a, b) => b.score - a.score);
    scored.forEach((e, i) => { e.rank = i + 1; });

    res.json(serialize({ leaderboard: scored.slice(0, 20), total: scored.length }));
  } catch (error: any) {
    console.error('[Leaderboard] Nearby error:', error.message);
    res.json({ leaderboard: [], total: 0 });
  }
});

// GET /api/leaderboard/friends - Get friends leaderboard
router.get('/friends', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Get friend IDs
    const friendships = await prisma.friends.findMany({
      where: {
        OR: [{ user_id: userId }, { friend_id: userId }],
        status: 'accepted',
      },
    });

    const friendIds = friendships.map((f: any) =>
      f.user_id.toString() === userId.toString() ? f.friend_id : f.user_id
    );

    if (friendIds.length === 0) {
      return res.json({ leaderboard: [], total: 0, message: 'Add friends to see their rankings' });
    }

    const friends = await prisma.users.findMany({
      where: { id: { in: friendIds } },
      include: {
        user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 },
      },
    });

    const scored = [];
    for (const user of friends) {
      const p = Array.isArray(user.user_profiles) ? user.user_profiles[0] : user.user_profiles;
      const matchCount = await prisma.matches.count({
        where: {
          OR: [{ user1_id: user.id }, { user2_id: user.id }],
          is_active: true,
        },
      });

      scored.push({
        user_id: Number(user.id),
        name: p?.display_name || user.name || 'Anonymous',
        avatar_url: p?.avatar_url || null,
        score: matchCount * 10,
        matches: matchCount,
      rank: 0,
      });
    }

    console.log('[Leaderboard] Scored', scored.length, 'users, top score:', scored[0]?.score || 0);
  scored.sort((a, b) => b.score - a.score);
    scored.forEach((e, i) => { e.rank = i + 1; });

    res.json(serialize({ leaderboard: scored, total: scored.length }));
  } catch (error: any) {
    console.error('[Leaderboard] Friends error:', error.message);
    res.json({ leaderboard: [], total: 0 });
  }
});

export default router;
