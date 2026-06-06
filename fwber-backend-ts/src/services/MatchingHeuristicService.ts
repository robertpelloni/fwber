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
    const [answers1, answers2] = await Promise.all([
      this.getUserAnswers(user1Id),
      this.getUserAnswers(user2Id)
    ]);

    if (answers1.length === 0 || answers2.length === 0) {
      return 0; // Or some base score
    }

    const sat1 = this.calculateSatisfaction(answers1, answers2);
    const sat2 = this.calculateSatisfaction(answers2, answers1);

    if (sat1 === 0 || sat2 === 0) return 0;

    // Geometric mean
    return Math.sqrt(sat1 * sat2);
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
}
