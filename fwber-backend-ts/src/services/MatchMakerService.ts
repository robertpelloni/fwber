import type { User, MatchBounty, MatchAssist } from '@prisma/client';
import { PrismaClient } from '@prisma/client';
import prisma from '../lib/prisma.js';
import { TokenDistributionService } from './TokenDistributionService.js';

export class MatchMakerService {
  private tokenService: TokenDistributionService;
  private static MATCHMAKER_BONUS = 50;

  constructor() {
    this.tokenService = new TokenDistributionService();
  }

  async createBounty(userId: number, tokenReward: number): Promise<MatchBounty> {
    return await prisma.$transaction(async (tx) => {
      // 1. Escrow tokens
      await this.tokenService.spendTokens(
        userId,
        tokenReward,
        `Escrow for match bounty: ${tokenReward} FWB`
      );

      // 2. Generate slug
      let slug = '';
      let exists = true;
      while (exists) {
        slug = Math.random().toString(36).substring(2, 10);
        const bounty = await tx.matchBounty.findUnique({ where: { slug } });
        if (!bounty) exists = false;
      }

      const expiresAt = new Date();
      expiresAt.setDate(expiresAt.getDate() + 30);

      return await tx.matchBounty.create({
        data: {
          user_id: userId,
          slug,
          token_reward: tokenReward,
          status: 'active',
          expires_at: expiresAt
        }
      });
    });
  }

  async suggestCandidate(bountySlug: string, matchmakerId: number, candidateId: number): Promise<MatchAssist> {
    const bounty = await prisma.matchBounty.findUnique({
      where: { slug: bountySlug },
      include: { user: true }
    });

    if (!bounty) throw new Error('Bounty not found');
    if (candidateId === bounty.user_id) throw new Error('Cannot suggest the bounty creator to themselves.');

    const existing = await prisma.matchAssist.findUnique({
      where: {
        matchmaker_id_subject_id_target_id: {
          matchmaker_id: matchmakerId,
          subject_id: bounty.user_id,
          target_id: candidateId
        }
      }
    });

    if (existing) return existing;

    return await prisma.matchAssist.create({
      data: {
        match_bounty_id: bounty.id,
        matchmaker_id: matchmakerId,
        subject_id: bounty.user_id,
        target_id: candidateId,
        status: 'suggested'
      }
    });
  }

  async processMatch(user1Id: number, user2Id: number): Promise<void> {
    const assists = await prisma.matchAssist.findMany({
      where: {
        OR: [
          { subject_id: user1Id, target_id: user2Id },
          { subject_id: user2Id, target_id: user1Id }
        ],
        status: { not: 'matched' }
      },
      include: { bounty: true }
    });

    for (const assist of assists) {
      await this.rewardWingman(assist.id);
    }
  }

  private async rewardWingman(assistId: number): Promise<void> {
    await prisma.$transaction(async (tx) => {
      const assist = await tx.matchAssist.update({
        where: { id: assistId },
        data: { status: 'matched' },
        include: { bounty: true }
      });

      let rewardAmount = MatchMakerService.MATCHMAKER_BONUS;
      let memo = `Wingman Bonus: You successfully matched User ${assist.subject_id} and User ${assist.target_id}!`;

      if (assist.bounty) {
        rewardAmount = assist.bounty.token_reward;
        memo = `Bounty Claimed! You matched User ${assist.subject_id} for their '${assist.bounty.slug}' bounty.`;

        await tx.matchBounty.update({
          where: { id: assist.bounty.id },
          data: { status: 'fulfilled' }
        });
      }

      await this.tokenService.awardTokens(
        assist.matchmaker_id,
        rewardAmount,
        'matchmaker_bonus',
        memo,
        {},
        tx
      );
    });
  }
}
