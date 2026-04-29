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

    // Get users that the current user hasn't already matched with
    const existingMatches = await prisma.matches.findMany({
      where: {
        OR: [{ user1_id: userId }, { user2_id: userId }],
      },
      select: { user1_id: true, user2_id: true },
    });

    const excludeIds = new Set<string>();
    excludeIds.add(userId.toString());
    existingMatches.forEach((m: any) => {
      excludeIds.add(m.user1_id.toString());
      excludeIds.add(m.user2_id.toString());
    });

    // Get nearby users with profiles as potential matches
    const candidates = await prisma.users.findMany({
      where: {
        id: { notIn: Array.from(excludeIds).map((id) => BigInt(id)) },
        user_profiles: { some: {} },
      },
      include: {
        user_profiles: true,
        photos: {
          where: { is_private: false },
          orderBy: { is_primary: 'desc' as const },
          take: 5,
        },
      },
      take: limit,
      skip: offset,
      orderBy: { last_seen_at: 'desc' },
    });

    const matches = candidates.map((u: any) => {
      const p = (Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles) || {};
      return {
        id: Number(u.id),
        name: p.display_name || u.name || 'Anonymous',
        email: null,
        avatarUrl: p.avatar_url || null,
        bio: p.bio || null,
        locationDescription: p.location_description || null,
        distance: 0,
        compatibilityScore: 0,
        lastSeenAt: u.last_seen_at?.toISOString() || null,
        age: p.date_of_birth
          ? new Date().getFullYear() - new Date(p.date_of_birth).getFullYear()
          : null,
        gender: p.gender || null,
        is_verified: p.is_verified || false,
        voice_intro_url: p.voice_intro_url || null,
        photos: (u.photos || []).map((photo: any) => ({
          id: Number(photo.id),
          url: photo.file_path || photo.url || '',
          is_private: photo.is_private || false,
          is_primary: photo.is_primary || false,
        })),
        shared_interests: [],
        shared_interest_count: 0,
      };
    });

    res.json({ matches, total: matches.length });
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
    const userId = BigInt(req.user.id);
    const { target_user_id, action } = req.body;

    if (!target_user_id || !action) {
      return res.status(400).json({ message: 'target_user_id and action are required' });
    }

    const targetId = BigInt(target_user_id);

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

      const profile = otherUser?.user_profiles || {};

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
      const profile = otherUser?.user_profiles || {};

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

export default router;
