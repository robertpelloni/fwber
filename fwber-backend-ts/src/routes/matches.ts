import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

/**
 * GET /api/matches - Get potential matches for the authenticated user
 * Query params: limit, offset, min_score, max_distance
 */
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const limit = parseInt(req.query.limit as string) || 20;
    const offset = parseInt(req.query.offset as string) || 0;
    const userLat = req.query.latitude ? parseFloat(String(req.query.latitude)) : null;
    const userLng = req.query.longitude ? parseFloat(String(req.query.longitude)) : null;

    // Exclude existing matches and self
    const existingMatches = await prisma.matches.findMany({
      where: { OR: [{ user1_id: userId }, { user2_id: userId }] },
      select: { user1_id: true, user2_id: true },
    });
    const excludeIds = new Set<string>([userId.toString()]);
    existingMatches.forEach((m: any) => { excludeIds.add(m.user1_id.toString()); excludeIds.add(m.user2_id.toString()); });

    // Get current user's profile for preference matching
    const myProfile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    const myPrefs: any = parseJson(myProfile?.preferences);
    const myInterests: string[] = parseArr(myProfile?.interests);
    const myLookingFor: string[] = parseArr(myProfile?.looking_for);
    const myGender = myProfile?.gender;
    const myOrientation = myProfile?.sexual_orientation;
    const myAge = myProfile?.date_of_birth
      ? new Date().getFullYear() - new Date(myProfile.date_of_birth as any).getFullYear()
      : null;

    // Preference ranges
    const prefMinAge = myPrefs?.age_range_min || 18;
    const prefMaxAge = myPrefs?.age_range_max || 99;

    // Fetch more candidates than needed so we can score/filter
    const candidates = await prisma.users.findMany({
      where: {
        id: { notIn: Array.from(excludeIds).map((id) => BigInt(id)) },
        user_profiles: { some: {} },
      },
      include: {
        user_profiles: true,
        photos: { where: { is_private: false }, orderBy: { is_primary: 'desc' as const }, take: 5 },
      },
      take: limit * 5,
      skip: offset,
    });

    let scored = candidates.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      const theirAge = p.date_of_birth
        ? new Date().getFullYear() - new Date(p.date_of_birth as any).getFullYear()
        : null;
      const theirGender = p.gender || null;
      const theirInterests: string[] = parseArr(p.interests);
      const sharedInterests = myInterests.filter(i => theirInterests.includes(i));

      // Score calculation
      let score = 20; // base

      // Shared interests (+10 each, max 40)
      score += Math.min(sharedInterests.length * 10, 40);

      // Age preference match (+15 if within range)
      if (theirAge && theirAge >= prefMinAge && theirAge <= prefMaxAge) score += 15;

      // Mutual gender preference (+10)
      // Simple: if user is straight, prefer opposite gender; if gay/lesbian, prefer same; bi/pan get bonus either way
      if (myOrientation === 'straight' && theirGender && theirGender !== myGender) score += 10;
      else if ((myOrientation === 'gay' || myOrientation === 'lesbian') && theirGender === myGender) score += 10;
      else if (myOrientation === 'bisexual' || myOrientation === 'pansexual' || myOrientation === 'queer') score += 10;

      // Location proximity (+15 if close)
      let distanceMeters = 0;
      if (userLat && userLng && p.location_latitude && p.location_longitude) {
        const R = 6371;
        const dLat = ((Number(p.location_latitude) - userLat) * Math.PI) / 180;
        const dLon = ((Number(p.location_longitude) - userLng) * Math.PI) / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos((userLat * Math.PI) / 180) * Math.cos((Number(p.location_latitude) * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
        distanceMeters = Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) * 1000);
        if (distanceMeters < 5000) score += 15;
        else if (distanceMeters < 25000) score += 8;
        else if (distanceMeters < 50000) score += 3;
      }

      // Verified bonus (+5)
      if (p.is_verified) score += 5;

      // Bio bonus (+5)
      if (p.bio && p.bio.length > 20) score += 5;

      score = Math.min(99, score);

      return {
        id: Number(u.id),
        name: p.display_name || u.name || 'Anonymous',
        email: null,
        avatarUrl: p.avatar_url || null,
        bio: p.bio || null,
        locationDescription: p.location_description || null,
        distance: distanceMeters,
        compatibilityScore: score,
        lastSeenAt: u.last_seen_at?.toISOString() || null,
        age: theirAge,
        gender: theirGender,
        is_verified: p.is_verified || false,
        voice_intro_url: p.voice_intro_url || null,
        photos: (u.photos || []).map((photo: any) => ({
          id: Number(photo.id),
          url: photo.file_path || photo.url || '',
          is_private: photo.is_private || false,
          is_primary: photo.is_primary || false,
        })),
        shared_interests: sharedInterests,
        shared_interest_count: sharedInterests.length,
      };
    });

    // Sort by score descending
    scored.sort((a, b) => b.compatibilityScore - a.compatibilityScore);

    res.json({ matches: scored.slice(0, limit), total: scored.length });
  } catch (error: any) {
    console.error('[Matches] Error fetching matches:', error.message);
    res.json({ matches: [], total: 0 });
  }
});

/**
 * POST /api/matches/action - Perform match action (like, pass, super_like)
 */
router.post('/action', authenticate, async (req: any, res) => {
  try {
    // Standardize IDs
    const userId = BigInt(req.user.id);
    const targetUserIdRaw = req.body.target_user_id || req.body.targetUserId;
    const action = req.body.action as string;

    if (!targetUserIdRaw || !action) {
      return res.status(400).json({ message: 'target_user_id and action are required' });
    }

    const targetId = BigInt(targetUserIdRaw);

    if (action === 'pass') {
      return res.json({ action: 'pass', is_match: false, message: 'Passed' });
    }

    // Check if target user already liked current user
    const existingLike = await prisma.matches.findFirst({
      where: {
        OR: [
          { user1_id: targetId, user2_id: userId },
          { user1_id: userId, user2_id: targetId },
        ],
      },
    });

    if (existingLike) {
      // Mutual match - update status to accepted
      await prisma.matches.update({
        where: { id: existingLike.id },
        data: { status: 'accepted', is_active: true },
      });

      return res.json({
        action,
        is_match: true,
        message: "It's a match!",
        match_id: Number(existingLike.id),
      });
    }

    // Create new match entry (pending - waiting for other user)
    const match = await prisma.matches.create({
      data: {
        user1_id: userId,
        user2_id: targetId,
        status: 'pending',
        match_score: action === 'super_like' ? 100 : 50,
      },
    });

    res.json({
      action,
      is_match: false,
      message: action === 'super_like' ? 'Super liked!' : 'Liked!',
      match_id: Number(match.id),
    });
  } catch (error: any) {
    console.error('[Matches] Error performing action:', error.message);
    res.status(500).json({ message: 'Failed to perform action' });
  }
});

/**
 * GET /api/matches/established - Get mutual/established matches
 */
router.get('/established', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const mutualMatches = await prisma.matches.findMany({
      where: {
        OR: [{ user1_id: userId }, { user2_id: userId }],
        status: 'accepted',
        is_active: true,
      },
      include: {
        users_matches_user1_idTousers: {
          include: {
            user_profiles: true,
            photos: {
              where: { is_private: false },
              orderBy: { is_primary: 'desc' as const },
              take: 5,
            },
          },
        },
        users_matches_user2_idTousers: {
          include: {
            user_profiles: true,
            photos: {
              where: { is_private: false },
              orderBy: { is_primary: 'desc' as const },
              take: 5,
            },
          },
        },
      },
      orderBy: { last_message_at: 'desc' },
    });

    const data = mutualMatches.map((match: any) => {
      const isUser1 = match.user1_id.toString() === userId.toString();
      const otherUser = isUser1
        ? match.users_matches_user2_idTousers
        : match.users_matches_user1_idTousers;

      const rawProfile = otherUser?.user_profiles;
      const profile = Array.isArray(rawProfile) ? rawProfile[0] || {} : (rawProfile || {});

      return {
        id: Number(match.id),
        other_user: {
          id: Number(otherUser?.id),
          name: otherUser?.name || 'Anonymous',
          email: otherUser?.email || null,
          last_seen_at: otherUser?.last_seen_at?.toISOString() || null,
          profile: {
            display_name: profile.display_name || otherUser?.name || 'Anonymous',
            bio: profile.bio || null,
            date_of_birth: profile.date_of_birth?.toISOString() || null,
            gender: profile.gender || null,
            looking_for: profile.looking_for || [],
            location_latitude: profile.location_latitude
              ? Number(profile.location_latitude)
              : null,
            location_longitude: profile.location_longitude
              ? Number(profile.location_longitude)
              : null,
            location_description: profile.location_description || null,
            avatar_url: profile.avatar_url || null,
          },
          photos: (otherUser?.photos || []).map((p: any) => ({
            id: Number(p.id),
            url: p.file_path || p.url || '',
            is_private: p.is_private || false,
            is_primary: p.is_primary || false,
          })),
        },
        match_score: match.match_score,
        created_at: match.created_at?.toISOString() || null,
        last_message_at: match.last_message_at?.toISOString() || null,
      };
    });

    res.json({ data });
  } catch (error: any) {
    console.error('[Matches] Error fetching established matches:', error.message);
    res.json({ data: [] });
  }
});

/**
 * GET /api/matches/history - Get match history
 */
router.get('/history', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const history = await prisma.matches.findMany({
      where: {
        OR: [{ user1_id: userId }, { user2_id: userId }],
      },
      include: {
        users_matches_user1_idTousers: {
          include: { user_profiles: true },
        },
        users_matches_user2_idTousers: {
          include: { user_profiles: true },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 50,
    });

    const data = history.map((match: any) => {
      const isUser1 = match.user1_id.toString() === userId.toString();
      const otherUser = isUser1
        ? match.users_matches_user2_idTousers
        : match.users_matches_user1_idTousers;
      const rawProfile = otherUser?.user_profiles;
      const profile = Array.isArray(rawProfile) ? rawProfile[0] || {} : (rawProfile || {});

      return {
        id: Number(match.id),
        name: profile.display_name || otherUser?.name || 'Anonymous',
        avatarUrl: profile.avatar_url || null,
        bio: profile.bio || null,
        compatibilityScore: match.match_score,
        status: match.status,
        createdAt: match.created_at?.toISOString() || null,
      };
    });

    res.json({ data });
  } catch (error: any) {
    console.error('[Matches] Error fetching history:', error.message);
    res.json({ data: [] });
  }
});

/**
 * POST /api/matches/:matchId/feedback - Submit date feedback
 */
router.post('/:matchId/feedback', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const matchId = BigInt(req.params.matchId);
    const { rating, feedback_text, safety_concerns } = req.body;

    if (!rating) {
      return res.status(400).json({ message: 'Rating is required' });
    }

    // Verify user is part of this match
    const match = await prisma.matches.findFirst({
      where: {
        id: matchId,
        OR: [{ user1_id: userId }, { user2_id: userId }],
      },
    });

    if (!match) {
      return res.status(404).json({ message: 'Match not found' });
    }

    res.json({
      message: 'Feedback submitted',
      submitted: true,
      feedback: {
        id: Date.now(),
        rating,
        feedback_text: feedback_text || null,
        safety_concerns: safety_concerns || false,
      },
    });
  } catch (error: any) {
    console.error('[Matches] Error submitting feedback:', error.message);
    res.status(500).json({ message: 'Failed to submit feedback' });
  }
});

/**
 * GET /api/matches/:matchId/feedback - Check date feedback
 */
router.get('/:matchId/feedback', authenticate, async (req: any, res) => {
  try {
    res.json({ message: 'No feedback yet', submitted: false });
  } catch (error: any) {
    res.status(500).json({ message: 'Failed to check feedback' });
  }
});

/**
 * POST /api/matches/:matchId/insights/unlock - Unlock match insights (premium)
 */
router.post('/:matchId/insights/unlock', authenticate, async (req: any, res) => {
  try {
    res.json({ message: 'Insight unlocked', success: true });
  } catch (error: any) {
    res.status(500).json({ message: 'Failed to unlock insight' });
  }
});

/**
 * GET /api/matches/insights/unlocked - Get unlocked insights
 */
router.get('/insights/unlocked', authenticate, async (req: any, res) => {
  res.json({ data: [] });
});

/**
 * GET /api/matches/insights/available - Get available insights
 */
router.get('/insights/available', authenticate, async (req: any, res) => {
  res.json({ data: [] });
});

// ─── Helpers ───────────────────────────────────────────────────────────────

function parseJson(val: any): any {
  if (val && typeof val === 'object') return val;
  if (typeof val === 'string') { try { return JSON.parse(val); } catch { return {}; } }
  return {};
}

function parseArr(val: any): string[] {
  if (Array.isArray(val)) return val;
  if (typeof val === 'string') { try { return JSON.parse(val); } catch { return []; } }
  return [];
}

export default router;
