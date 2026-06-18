import prisma from '../lib/prisma.js';

export class MatchingHeuristicService {
  /**
   * Calculate compatibility score between two users based on matching questions.
   * Algorithm inspired by OkCupid:
   * 1. Calculate Satisfaction A: How well User B's answers meet User A's requirements.
   * 2. Calculate Satisfaction B: How well User A's answers meet User B's requirements.
   * 3. Overall Score = (Satisfaction A * Satisfaction B) ^ (1/n)
   */
  async calculateCompatibility(user1Id: bigint, user2Id: bigint): Promise<number> {
    const [answers1, answers2, proximityScore] = await Promise.all([
      this.getUserAnswers(user1Id),
      this.getUserAnswers(user2Id),
      this.calculateProximityScore(user1Id, user2Id)
    ]);

    if (answers1.length === 0 || answers2.length === 0) {
      return proximityScore * 0.2; // Base score on proximity if no answers
    }

    const sat1 = this.calculateSatisfaction(answers1, answers2);
    const sat2 = this.calculateSatisfaction(answers2, answers1);

    if (sat1 === 0 || sat2 === 0) return proximityScore * 0.1;

    // Geometric mean of matching answers (80% weight) + proximity (20% weight)
    const matchingScore = Math.sqrt(sat1 * sat2);
    return (matchingScore * 0.8) + (proximityScore * 0.2);
  }

  /**
   * Calculate proximity score based on how recently and closely users have been near each other.
   */
  private async calculateProximityScore(user1Id: bigint, user2Id: bigint): Promise<number> {
    try {
      const loc1 = await prisma.user_locations.findFirst({
        where: { user_id: user1Id, is_active: true },
        orderBy: { last_updated: 'desc' }
      });

      const loc2 = await prisma.user_locations.findFirst({
        where: { user_id: user2Id, is_active: true },
        orderBy: { last_updated: 'desc' }
      });

      if (!loc1 || !loc2) return 0;

      // Basic distance calculation (Haversine simplified for small distances)
      const lat1 = Number(loc1.latitude);
      const lon1 = Number(loc1.longitude);
      const lat2 = Number(loc2.latitude);
      const lon2 = Number(loc2.longitude);

      const R = 6371; // Earth's radius in km
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
      const distance = R * c;

      // Score based on distance (within 10km is high score)
      if (distance < 1) return 1.0;
      if (distance < 5) return 0.8;
      if (distance < 10) return 0.5;
      if (distance < 50) return 0.2;

      return 0;
    } catch (err) {
      console.error('[MatchingHeuristic] Proximity calculation error:', err);
      return 0;
    }
  }

  private async getUserAnswers(userId: bigint) {
    return await prisma.user_matching_answers.findMany({
      where: { user_id: userId },
      include: {
        matching_questions: true
      }
    });
  }

  private calculateSatisfaction(subjectAnswers: any[], targetAnswers: any[]): number {
    let totalPossiblePoints = 0;
    let earnedPoints = 0;

    for (const sub of subjectAnswers) {
      const tar = targetAnswers.find(a => a.question_id === sub.question_id);
      if (!tar) continue;

      const importancePoints = this.getImportancePoints(sub.importance);
      totalPossiblePoints += importancePoints;

      const acceptedOptions = Array.isArray(sub.accepted_option_ids)
        ? (sub.accepted_option_ids as unknown as string[]).map(id => BigInt(id))
        : [];

      if (acceptedOptions.includes(tar.chosen_option_id)) {
        earnedPoints += importancePoints;
      }
    }

    if (totalPossiblePoints === 0) return 0;
    return earnedPoints / totalPossiblePoints;
  }

  private getImportancePoints(importance: number): number {
    switch (importance) {
      case 0: return 0;     // Irrelevant
      case 1: return 1;     // A little important
      case 2: return 10;    // Somewhat important
      case 3: return 50;    // Very important
      case 4: return 250;   // Extremely important
      default: return 1;
    }
  }

  /**
   * Calculates emotional affinity based on current aura/emotion alignment.
   */
  async calculateAuraCompatibility(userId1: bigint, userId2: bigint): Promise<{ score: number, mood: string }> {
    const [p1, p2] = await Promise.all([
        prisma.user_profiles.findFirst({ where: { user_id: userId1 } }),
        prisma.user_profiles.findFirst({ where: { user_id: userId2 } })
    ]);

    const e1 = (p1?.current_emotion || 'Neutral').toLowerCase();
    const e2 = (p2?.current_emotion || 'Neutral').toLowerCase();

    if (e1 === e2 && e1 !== 'neutral') {
        return { score: 1.0, mood: `Both feeling ${p1?.current_emotion}` };
    }

    // Complementary moods
    const complements: Record<string, string[]> = {
        thoughtful: ['mysterious', 'melancholic'],
        excited: ['happy', 'vibrant'],
        cynical: ['mysterious', 'thoughtful'],
        happy: ['excited', 'thoughtful']
    };

    if (complements[e1]?.includes(e2) || complements[e2]?.includes(e1)) {
        return { score: 0.8, mood: 'Complementary Headspace' };
    }

    return { score: 0.3, mood: 'Different Vibes' };
  }
}
