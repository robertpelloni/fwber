import type { match_bounties, match_assists } from '@prisma/client';
import prisma from '../lib/prisma.js';
import { TokenDistributionService } from './TokenDistributionService.js';
import { AutonomousService } from './AutonomousService.js';

export class MatchMakerService {
  private tokenService: TokenDistributionService;
  private static MATCHMAKER_BONUS = 50;

  constructor() {
    this.tokenService = new TokenDistributionService();
  }

  async createBounty(userId: bigint, tokenReward: number): Promise<match_bounties> {
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
        const bounty = await tx.match_bounties.findUnique({ where: { slug } });
        if (!bounty) exists = false;
      }

      const expiresAt = new Date();
      expiresAt.setDate(expiresAt.getDate() + 30);

      return await tx.match_bounties.create({

      const bounty = await tx.match_bounties.create({
        data: {
          user_id: userId,
          slug,
          token_reward: tokenReward,
          status: 'active',
          expires_at: expiresAt
        }
      });
      await AutonomousService.logAction('Match Bounty Created', 'Completed', { userId, slug, tokenReward });
      return bounty;
    });
  }

  async suggestCandidate(bountySlug: string, matchmakerId: bigint, candidateId: bigint): Promise<match_assists> {
    const bounty = await prisma.match_bounties.findUnique({
      where: { slug: bountySlug },
    });

    if (!bounty) throw new Error('Bounty not found');
    if (candidateId === bounty.user_id) throw new Error('Cannot suggest the bounty creator to themselves.');

    const existing = await prisma.match_assists.findUnique({
      where: {
        matchmaker_id_subject_id_target_id: {
          matchmaker_id: matchmakerId,
          subject_id: bounty.user_id,
          target_id: candidateId
        }
      }
    });

    if (existing) return existing;

    return await prisma.match_assists.create({

    const assist = await prisma.match_assists.create({
      data: {
        match_bounty_id: bounty.id,
        matchmaker_id: matchmakerId,
        subject_id: bounty.user_id,
        target_id: candidateId,
        status: 'suggested'
      }
    });
    await AutonomousService.logAction('Match Assist Suggested', 'Completed', { matchmakerId, subjectId: bounty.user_id, targetId: candidateId });
    return assist;
  }

  async processMatch(user1Id: bigint, user2Id: bigint): Promise<void> {
    const assists = await prisma.match_assists.findMany({
      where: {
        OR: [
          { subject_id: user1Id, target_id: user2Id },
          { subject_id: user2Id, target_id: user1Id }
        ],
        status: { not: 'matched' }
      },
      include: { match_bounties: true }
    });

    for (const assist of assists) {
      await this.rewardWingman(assist.id);
    }
  }

  private async rewardWingman(assistId: bigint): Promise<void> {
    await prisma.$transaction(async (tx) => {
      const assist = await tx.match_assists.update({
        where: { id: assistId },
        data: { status: 'matched' },
        include: { match_bounties: true }
      });

      let rewardAmount = MatchMakerService.MATCHMAKER_BONUS;
      let memo = `Wingman Bonus: You successfully matched User ${assist.subject_id} and User ${assist.target_id}!`;

      if (assist.match_bounties) {
        rewardAmount = assist.match_bounties.token_reward;
        memo = `Bounty Claimed! You matched User ${assist.subject_id} for their '${assist.match_bounties.slug}' bounty.`;

        await tx.match_bounties.update({
          where: { id: assist.match_bounties.id },
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
      await AutonomousService.logAction('Wingman Reward Issued', 'Completed', { matchmakerId: assist.matchmaker_id, rewardAmount, assistId });
    });
  }
}
