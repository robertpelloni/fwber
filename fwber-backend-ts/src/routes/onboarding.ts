import { Router } from 'express';
import prisma from '../lib/prisma.js';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// POST /api/onboarding/complete - Mark onboarding as completed
router.post('/complete', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { name, gender, date_of_birth, bio, interests, looking_for } = req.body;

    // Build profile updates from onboarding data
    const profileUpdate: any = {};
    if (name) profileUpdate.display_name = name;
    if (gender) profileUpdate.gender = gender;
    if (date_of_birth) {
      const dob = new Date(date_of_birth);
      if (!isNaN(dob.getTime())) {
        profileUpdate.date_of_birth = dob;
        profileUpdate.birthdate = dob;
      }
    }
    if (bio) profileUpdate.bio = bio;
    if (interests) profileUpdate.interests = typeof interests === 'string' ? interests : JSON.stringify(interests);
    if (looking_for) profileUpdate.looking_for = typeof looking_for === 'string' ? looking_for : JSON.stringify(looking_for);

    // Get profile ID for upsert
    const profile = await prisma.user_profiles.findFirst({ where: { user_id: userId } });

    // Use transaction to update both user and profile
    await prisma.$transaction([
      prisma.users.update({
        where: { id: userId },
        data: { 
          onboarding_completed_at: new Date(),
          name: name || undefined
        },
      }),
      (profile ? 
        prisma.user_profiles.update({
          where: { id: profile.id },
          data: profileUpdate
        }) : 
        prisma.user_profiles.create({
          data: {
            user_id: userId,
            ...profileUpdate
          }
        })
      )
    ]);

    res.json({ message: 'Onboarding completed and profile updated' });
  } catch (error: any) {
    console.error('[POST /api/onboarding/complete]', error.message);
    res.status(500).json({ message: error.message || 'Failed to complete onboarding' });
  }
});

export default router;
