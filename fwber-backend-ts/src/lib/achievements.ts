import prisma from './prisma.js';

/**
 * Check and unlock achievements for a user based on their current stats.
 * Called after key actions: match, message, gift, checkin, profile update, photo upload, etc.
 *
 * Returns array of newly unlocked achievement slugs.
 */
export async function checkAndUnlockAchievements(userId: bigint): Promise<string[]> {
  const newlyUnlocked: string[] = [];

  try {
    // Get already-unlocked achievement IDs for this user
    const existing = await prisma.user_achievements.findMany({
      where: { user_id: userId },
      select: { achievement_id: true },
    });
    const earnedIds = new Set(existing.map(e => e.achievement_id.toString()));

    // Get all achievements
    const allAchievements = await prisma.achievements.findMany();
    const unearned = allAchievements.filter(a => !earnedIds.has(a.id.toString()));

    if (unearned.length === 0) return newlyUnlocked;

    // Gather user stats in parallel
    const [
      matchCount,
      messageCount,
      giftSentCount,
      photoCount,
      friendCount,
      checkinCount,
      journalCount,
      profile,
      user,
    ] = await Promise.all([
      prisma.matches.count({
        where: {
          OR: [{ user1_id: userId }, { user2_id: userId }],
          status: 'accepted',
        },
      }).catch(() => 0),
      prisma.messages.count({
        where: { sender_id: userId },
      }).catch(() => 0),
      prisma.user_gifts.count({
        where: { sender_id: userId },
      }).catch(() => 0),
      prisma.photos.count({
        where: { user_id: userId },
      }).catch(() => 0),
      prisma.friends.count({
        where: {
          OR: [{ user_id: userId }, { friend_id: userId }],
          status: 'accepted',
        },
      }).catch(() => 0),
      // Check if user has ever checked in (last_daily_bonus_at is set)
      prisma.users.findUnique({ where: { id: userId }, select: { last_daily_bonus_at: true } })
        .then(u => u?.last_daily_bonus_at ? 1 : 0).catch(() => 0),
      prisma.$queryRaw`SELECT COUNT(*) as count FROM journal_entries WHERE user_id = ${userId}`.then((r: any) => Number(r[0]?.count || 0)).catch(() => 0),
      prisma.user_profiles.findFirst({
        where: { user_id: userId },
      }).catch(() => null as any),
      prisma.users.findUnique({
        where: { id: userId },
        select: { email_verified_at: true, current_streak: true },
      }).catch(() => null as any),
    ]);

    // Calculate profile completeness
    let completenessPct = 0;
    if (profile) {
      let filled = 0;
      let total = 10;
      if (profile.bio && profile.bio !== 'No bio yet.' && profile.bio.length > 5) filled++;
      if (profile.gender) filled++;
      if (profile.date_of_birth) filled++;
      if (profile.location_description) filled++;
      if (profile.interests) filled++;
      if (profile.occupation) filled++;
      if (profile.education) filled++;
      if (profile.height_cm) filled++;
      if (profile.looking_for) filled++;
      if (profile.avatar_url) filled++;
      completenessPct = Math.round((filled / total) * 100);
    }

    const streak = user?.current_streak || 0;
    const emailVerified = !!user?.email_verified_at;

    // Map of slug -> check function
    const checks: Record<string, () => boolean> = {
      'profile_verified': () => emailVerified,
      'first_match': () => matchCount >= 1,
      'streak_7': () => streak >= 7,
      'streak_30': () => streak >= 30,
      'first_message': () => messageCount >= 1,
      'first_gift': () => giftSentCount >= 1,
      'profile_complete': () => completenessPct >= 75,
      'five_matches': () => matchCount >= 5,
      'first_photo': () => photoCount >= 1,
      'ten_matches': () => matchCount >= 10,
      'daily_checkin': () => checkinCount >= 1,
      'first_friend': () => friendCount >= 1,
      'gift_sent_5': () => giftSentCount >= 5,
      'journal_entry': () => journalCount >= 1,
      'verified_email': () => emailVerified,
    };

    // Check each unearned achievement
    for (const achievement of unearned) {
      const check = checks[achievement.slug];
      if (check && check()) {
        try {
          await prisma.user_achievements.create({
            data: {
              user_id: userId,
              achievement_id: achievement.id,
              unlocked_at: new Date(),
            },
          });

          // Award token reward
          if (achievement.token_reward && achievement.token_reward > 0) {
            await prisma.users.update({
              where: { id: userId },
              data: { token_balance: { increment: achievement.token_reward } },
            });

            try {
              await prisma.wallet_transactions.create({
                data: {
                  user_id: userId,
                  amount: achievement.token_reward,
                  type: 'reward',
                  description: `Achievement unlocked: ${achievement.name}`,
                  created_at: new Date(),
                },
              });
            } catch (_) {}
          }

          newlyUnlocked.push(achievement.slug);
        } catch (e: any) {
          // Duplicate key — already unlocked by another concurrent request
          if (!e.message?.includes('Unique')) {
            console.error('[Achievements] Unlock error:', e.message);
          }
        }
      }
    }

    if (newlyUnlocked.length > 0) {
      console.log(`[Achievements] User ${userId.toString()} unlocked: ${newlyUnlocked.join(', ')}`);
    }
  } catch (error: any) {
    console.error('[Achievements] checkAndUnlock error:', error.message);
  }

  return newlyUnlocked;
}
