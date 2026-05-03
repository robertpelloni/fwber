import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

/**
 * Helper: Parse JSON fields that may already be objects or strings
 */
function parseJsonField(val: any): any {
  if (!val) return val;
  if (typeof val === "object") return val;
  if (typeof val === "string") {
    try {
      return JSON.parse(val);
    } catch {
      return val;
    }
  }
  return val;
}

/**
 * Calculate Profile Completeness (Mirroring the profile.ts logic)
 */
async function calculateCompleteness(userId: bigint, profile: any) {
  if (!profile) return 0;

  const photoCount = await prisma.photos.count({
    where: { user_id: userId, is_private: false },
  });
  
  const interestsArr = Array.isArray(parseJsonField(profile.interests))
    ? parseJsonField(profile.interests)
    : [];
    
  const prefsRaw: any = parseJsonField(profile.preferences) || {};
  const prefs = typeof prefsRaw === "object" && !Array.isArray(prefsRaw) ? prefsRaw : {};
  const birthDate = profile.date_of_birth || profile.birthdate;

  let earned = 0;

  // Weights (Matching profile.ts)
  if (photoCount >= 3) earned += 25;
  if (profile.bio && String(profile.bio).trim().length >= 10) earned += 20;
  if (birthDate) earned += 10;
  if (profile.location_description || profile.location_latitude) earned += 10;
  if (interestsArr.length >= 3) earned += 10;
  if (profile.occupation || (prefs && prefs.occupation)) earned += 5;
  if (profile.education || (prefs && prefs.education)) earned += 5;
  if (profile.height_cm) earned += 3;
  if (profile.religion) earned += 3;
  if (profile.political_views) earned += 3;
  if (profile.drinking_status || (prefs && prefs.drinking)) earned += 3;
  if (profile.smoking_status || (prefs && prefs.smoking)) earned += 3;

  return earned;
}

// All routes require authentication
router.use(authenticate);

// GET /api/dashboard/stats
router.get('/stats', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const [
      user,
      profile,
      totalMatches,
      pendingMatches,
      acceptedMatches,
      profileViews,
      conversations,
    ] = await Promise.all([
      prisma.users.findUnique({ where: { id: userId } }),
      prisma.user_profiles.findFirst({ where: { user_id: userId } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }] } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }], status: 'pending' } }),
      prisma.matches.count({ where: { OR: [{ user1_id: userId }, { user2_id: userId }], status: 'accepted' } }),
      prisma.profile_views.count({ where: { viewed_user_id: userId } }).catch(() => 0),
      prisma.chatrooms.count().catch(() => 0),
    ]);

    const daysActive = user?.created_at
      ? Math.max(1, Math.floor((Date.now() - new Date(user.created_at as any).getTime()) / 86400000))
      : 1;

    const completionPercentage = await calculateCompleteness(userId, profile);

    res.json({
      total_matches: totalMatches,
      pending_matches: pendingMatches,
      accepted_matches: acceptedMatches,
      conversations,
      profile_views: typeof profileViews === 'number' ? profileViews : 0,
      today_views: 0,
      match_score_avg: 50,
      response_rate: 0,
      days_active: daysActive,
      last_login: user?.last_seen_at?.toISOString() || new Date().toISOString(),
      current_streak: user?.current_streak || 0,
      streak_just_updated: false,
      reverb_healthy: true,
      token_balance: Number(user?.token_balance || 0),
      completion_percentage: completionPercentage,
    });
  } catch (error: any) {
    console.error('[Dashboard] Stats error:', error.message);
    res.json({
      total_matches: 0, pending_matches: 0, accepted_matches: 0,
      conversations: 0, profile_views: 0, today_views: 0,
      match_score_avg: 0, response_rate: 0, days_active: 1,
      last_login: new Date().toISOString(), current_streak: 0,
      streak_just_updated: false, reverb_healthy: true, token_balance: 0,
    });
  }
});

// GET /api/dashboard/activity?limit=8
router.get('/activity', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const limit = parseInt(req.query.limit as string) || 8;
    const activities: any[] = [];

    // Recent matches
    const recentMatches = await prisma.matches.findMany({
      where: { OR: [{ user1_id: userId }, { user2_id: userId }], status: 'accepted' },
      include: {
        users_matches_user1_idTousers: { include: { user_profiles: true } },
        users_matches_user2_idTousers: { include: { user_profiles: true } },
      },
      orderBy: { created_at: 'desc' },
      take: 5,
    });
    for (const m of recentMatches) {
      const isUser1 = m.user1_id === userId;
      const partner = isUser1 ? m.users_matches_user2_idTousers : m.users_matches_user1_idTousers;
      const profile = Array.isArray(partner.user_profiles) ? partner.user_profiles[0] : partner.user_profiles;
      
      activities.push({
        id: `match-${m.id}`,
        type: 'match',
        user: {
          id: Number(partner.id),
          name: profile?.display_name || partner.name || 'Anonymous',
          avatar_url: profile?.avatar_url || null,
        },
        timestamp: m.created_at?.toISOString(),
      });
    }

    // Recent profile views
    const views = await prisma.profile_views.findMany({
      where: { viewed_user_id: userId },
      include: { users_profile_views_viewer_user_idTousers: { include: { user_profiles: true } } },
      orderBy: { created_at: 'desc' },
      take: 5,
    });
    for (const v of views) {
      const viewer = v.users_profile_views_viewer_user_idTousers;
      if (!viewer) continue;
      const profile = Array.isArray(viewer.user_profiles) ? viewer.user_profiles[0] : viewer.user_profiles;
      
      activities.push({
        id: `view-${v.id}`,
        type: 'view',
        user: {
          id: Number(viewer.id),
          name: profile?.display_name || viewer.name || 'Anonymous',
          avatar_url: profile?.avatar_url || null,
        },
        timestamp: v.created_at?.toISOString(),
      });
    }

    activities.sort((a, b) => new Date(b.timestamp || 0).getTime() - new Date(a.timestamp || 0).getTime());
    res.json(activities.slice(0, limit));
  } catch (error: any) {
    console.error('[Dashboard] Activity error:', error.message, error.stack);
    res.json([]);
  }
});

export default router;
