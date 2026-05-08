import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/settings/privacy/journals — get journal privacy settings
router.get('/privacy/journals', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: userId },
      select: { journal_circle_group_id: true },
    });
    res.json({
      data: {
        default_visibility: profile?.journal_circle_group_id ? 'circle' : 'private',
        allow_comments: true,
        allow_reactions: true,
        share_with_matches: false,
      },
    });
  } catch (error: any) {
    res.json({
      data: {
        default_visibility: 'private',
        allow_comments: true,
        allow_reactions: true,
        share_with_matches: false,
      },
    });
  }
});

// PUT /api/settings/privacy/journals — update journal privacy settings
router.put('/privacy/journals', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { default_visibility, allow_comments, allow_reactions, share_with_matches } = req.body || {};

    if (default_visibility) {
      await prisma.user_profiles.updateMany({
        where: { user_id: userId },
        data: { journal_circle_group_id: default_visibility === 'circle' ? BigInt(1) : null },
      }).catch(() => {});
    }

    res.json({
      data: {
        default_visibility: default_visibility || 'private',
        allow_comments: allow_comments !== undefined ? allow_comments : true,
        allow_reactions: allow_reactions !== undefined ? allow_reactions : true,
        share_with_matches: share_with_matches !== undefined ? share_with_matches : false,
      },
    });
  } catch (error: any) {
    res.json({
      data: {
        default_visibility: 'private',
        allow_comments: true,
        allow_reactions: true,
        share_with_matches: false,
      },
    });
  }
});

// GET /api/settings — general settings
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: {
        name: true,
        email: true,
        email_verified_at: true,
        two_factor_secret: true,
        two_factor_confirmed_at: true,
        is_moderator: true,
      },
    });
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: userId },
      select: {
        display_name: true,
        journal_circle_group_id: true,
        occupation: true,
        education: true,
      },
    });
    res.json({
      user: {
        name: user?.name,
        email: user?.email,
        email_verified: !!user?.email_verified_at,
        two_factor_enabled: !!user?.two_factor_confirmed_at,
        is_moderator: user?.is_moderator || false,
      },
      profile: {
        display_name: profile?.display_name,
        journal_privacy: profile?.journal_circle_group_id ? 'circle' : 'private',
        occupation: profile?.occupation,
        education: profile?.education,
      },
    });
  } catch (error: any) {
    res.json({ user: {}, profile: {} });
  }
});

export default router;
