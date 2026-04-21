import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/profile - Get current user's profile
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    const photos = await prisma.photos.findMany({
      where: { user_id: userId, is_private: false },
      orderBy: { is_primary: 'desc' },
    });
    res.json({ ...profile, photos });
  } catch (error: any) {
    res.json({});
  }
});

// POST /api/profile - Create/update profile
router.post('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const existing = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    if (existing) {
      const updated = await prisma.user_profiles.update({ where: { id: existing.id }, data: req.body });
      res.json(updated);
    } else {
      const created = await prisma.user_profiles.create({ data: { user_id: userId, ...req.body } });
      res.json(created);
    }
  } catch (error: any) {
    res.status(500).json({ message: 'Failed to update profile' });
  }
});

// PUT /api/profile - Update profile
router.put('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const existing = await prisma.user_profiles.findFirst({ where: { user_id: userId } });
    if (existing) {
      const updated = await prisma.user_profiles.update({ where: { id: existing.id }, data: req.body });
      res.json(updated);
    } else {
      const created = await prisma.user_profiles.create({ data: { user_id: userId, ...req.body } });
      res.json(created);
    }
  } catch (error: any) {
    res.status(500).json({ message: 'Failed to update profile' });
  }
});

// GET /api/profile/completeness
router.get('/completeness', authenticate, async (req: any, res) => {
  try {
    const profile = await prisma.user_profiles.findFirst({ where: { user_id: BigInt(req.user.id) } });
    if (!profile) return res.json({ completeness: 10, missing: ['profile'] });
    const fields: Record<string, boolean> = {
      display_name: !!profile.display_name, bio: !!profile.bio,
      avatar_url: !!profile.avatar_url, date_of_birth: !!profile.date_of_birth,
      gender: !!profile.gender, interests: !!profile.interests,
    };
    const filled = Object.values(fields).filter(Boolean).length;
    res.json({ completeness: Math.round((filled / Object.keys(fields).length) * 100), missing: Object.entries(fields).filter(([, v]) => !v).map(([k]) => k) });
  } catch { res.json({ completeness: 10, missing: ['profile'] }); }
});

// GET /api/profile/:id/views - Profile view history
router.get('/:id/views', authenticate, async (req: any, res) => {
  res.json({ views: [], total: 0 });
});

// GET /api/profile/:id/view-stats - Profile view statistics
router.get('/:id/view-stats', authenticate, async (req: any, res) => {
  res.json({ total_views: 0, unique_viewers: 0, today: 0, this_week: 0 });
});

export default router;
