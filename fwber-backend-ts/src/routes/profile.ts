import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/profile/completeness
router.get('/completeness', authenticate, async (req: any, res) => {
  try {
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: BigInt(req.user.id) },
    });

    if (!profile) {
      return res.json({ completeness: 10, missing: ['profile'] });
    }

    const fields: Record<string, boolean> = {
      display_name: !!profile.display_name,
      bio: !!profile.bio,
      avatar_url: !!profile.avatar_url,
      date_of_birth: !!profile.date_of_birth,
      gender: !!profile.gender,
      interests: !!profile.interests,
    };

    const filled = Object.values(fields).filter(Boolean).length;
    const total = Object.keys(fields).length;
    const completeness = Math.round((filled / total) * 100);
    const missing = Object.entries(fields).filter(([, v]) => !v).map(([k]) => k);

    res.json({ completeness, missing });
  } catch (error: any) {
    res.json({ completeness: 10, missing: ['profile'] });
  }
});

export default router;
