import { Router } from 'express';
import prisma from '../lib/prisma.js';
import { authenticate } from '../middleware/auth.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';

const router = Router();

// GET /api/onboarding/status - Check onboarding completion status
router.get('/status', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { onboarding_completed_at: true },
    });
    const profile = await prisma.user_profiles.findFirst({
      where: { user_id: userId },
      select: {
        gender: true,
        bio: true,
        date_of_birth: true,
        location_description: true,
        interests: true,
      },
    });

    const isComplete = !!user?.onboarding_completed_at;
    const missingSteps: string[] = [];
    if (!profile?.gender) missingSteps.push('gender');
    if (!profile?.bio) missingSteps.push('bio');
    if (!profile?.date_of_birth) missingSteps.push('date_of_birth');
    if (!profile?.location_description) missingSteps.push('location');

    res.json({
      completed: isComplete,
      completed_at: user?.onboarding_completed_at?.toISOString() || null,
      missing_steps: missingSteps,
      profile: profile ? {
        has_gender: !!profile.gender,
        has_bio: !!profile.bio,
        has_dob: !!profile.date_of_birth,
        has_location: !!profile.location_description,
        has_interests: !!(profile.interests),
      } : null,
    });
  } catch (error: any) {
    console.error('[GET /api/onboarding/status]', error.message);
    res.json({ completed: false, missing_steps: [], profile: null });
  }
});

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

    // Use transaction to update user, profile, and grant starter tokens
    const STARTER_TOKENS = 25;
    await prisma.$transaction([
      prisma.users.update({
        where: { id: userId },
        data: { 
          onboarding_completed_at: new Date(),
          name: name || undefined,
          token_balance: { increment: STARTER_TOKENS },
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

    // Record the starter bonus as a transaction
    try {
      await prisma.wallet_transactions.create({
        data: {
          user_id: userId,
          amount: STARTER_TOKENS,
          type: 'reward',
          description: 'Welcome bonus — complete your profile to earn more!',
          created_at: new Date(),
        },
      });
    } catch (_) {}

    res.json({ message: 'Onboarding completed and profile updated', tokens_awarded: STARTER_TOKENS });
  } catch (error: any) {
    console.error('[POST /api/onboarding/complete]', error.message);
    res.status(500).json({ message: error.message || 'Failed to complete onboarding' });
  }
});

export default router;
